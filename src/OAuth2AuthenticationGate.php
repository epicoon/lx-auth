<?php

namespace lx\auth;

use lx\ApplicationComponent;
use lx\ClassOfServiceInterface;
use lx\ClassOfServiceTrait;
use lx\AuthenticationInterface;

/**
 *
 * */
class OAuth2AuthenticationGate extends ApplicationComponent implements AuthenticationInterface, ClassOfServiceInterface {
	use ClassOfServiceTrait;

	const AUTH_PROBLEM_NO = 0;
	const AUTH_PROBLEM_TOKEN_NOT_RETRIEVED = 5;
	const AUTH_PROBLEM_TOKEN_NOT_FOUND = 10;
	const AUTH_PROBLEM_TOKEN_EXPIRED = 15;
	const AUTH_PROBLEM_USER_NOT_FOUND = 20;

	protected $checkTokenModule = null;
	protected $userAuthenticateFields = 'login';
	protected $userLoginField = 'login';
	protected $userPasswordField = 'password';
	protected $accessTokenLifetime = 300;
	protected $refreshTokenLifetime = 84600;
	protected $tokenGenerator = null;

	private $userModelName;
	private $authProblem;

	/**
	 * Принимает в конфигурации информацию о модели пользователя
	 * Первый способ:
	 * 'userModel' => 'some/service.modelName'
	 * Второй способ:
	 * 'userModel' => [
	 * 		'service' => 'some/service',
	 * 		'name' => 'modelName'
	 * ]
	 * */
	public function __construct($config = []) {
		parent::__construct($config);

		$this->authProblem = self::AUTH_PROBLEM_NO;

		$userModel = $config['userModel'];
		if (is_string($userModel)) {
			$this->userModelName = $userModel;
		} elseif (is_array($userModel)) {
			$this->userModelName = $userModel['service'] . '.' . $userModel['name'];
		}
	}

	/**
	 * Попытка аутентифицировать пользователя:
	 * - Проверяем данные запроса (заголовок Authorization, куки) - ищем токен доступа
	 * - Если токен не найден или закончился срок действия - компонент "пользователь" остается гостем
	 * - По токену ищем модель AccessToken
	 * - Если модель не найдена - компонент "пользователь" остается гостем
	 * - По полю модели "id_user" ищем модель пользователя
	 * - Если модель пользователя не найдена - компонент "пользователь" остается гостем
	 * */
	public function authenticateUser() {
		$accessToken = $this->retrieveToken();
		if (!$accessToken) {
			$this->authProblem = self::AUTH_PROBLEM_TOKEN_NOT_RETRIEVED;
			return;
		}

		$accessTokenManager = $this->getModelManager('AccessToken');
		$accessTokenModel = $accessTokenManager->loadModel(['token' => $accessToken]);
		if (!$accessTokenModel) {
			$this->authProblem = self::AUTH_PROBLEM_TOKEN_NOT_FOUND;
			return;
		}

		$now = (new \DateTime())->format('Y-m-d H:i:s');
		$expire = (new \DateTime($accessTokenModel->expire))->format('Y-m-d H:i:s');
		if ($expire <= $now) {
			\lx::$dialog->addMessage('expired');
			$this->authProblem = self::AUTH_PROBLEM_TOKEN_EXPIRED;
			return;
		}

		$userManager = $this->getUserManager();
		$user = $userManager->loadModel($accessTokenModel->id_user);
		if (!$user) {
			$this->authProblem = self::AUTH_PROBLEM_USER_NOT_FOUND;
			return;
		}

		\lx::$components->user->setData($user);
	}

	/**
	 * Сформировать ответ, пытающийся авторизовать пользователя
	 * */
	public function responseToAuthenticate($responseSource) {
		switch ($this->authProblem) {
			// Группа проблем, где можно инициировать аутентификацию
			case self::AUTH_PROBLEM_TOKEN_NOT_RETRIEVED:
			case self::AUTH_PROBLEM_TOKEN_EXPIRED:
				// Послать модуль, который поищет токен доступа
				if ($this->checkTokenModule) {
					$arr = explode(':', $this->checkTokenModule);
					$service  = $arr[0];
					$module = $arr[1];
				} else {
					$service = $this->getServiceName();
					$module = 'getToken';
				}

				$responseSource->setData([
					'service' => $service,
					'module' => $module,
				]);
				break;

			// Группа проблем, не разрешающихся аутентификацией
			case self::AUTH_PROBLEM_NO:
				// Проблем при аутентификации не было, но раз наложены ограничения при авторизации - доступа нет
				return false;
			case self::AUTH_PROBLEM_TOKEN_NOT_FOUND:
				// Токен мы получили, но в своей системе не нашли - подозрительная ситуация
				return false;
			case self::AUTH_PROBLEM_USER_NOT_FOUND:
				// Токен получили, нашли, но не нашли по нему пользователя - возможно ошибка сервера
				return false;
		}

		return $responseSource;
	}

	/**
	 * Js-расширение для клиента
	 * */
	public function getJs() {
		return "lx.auth = function(config){
			let token = lx.Storage.get('lxauthtoken');
			if (!token) return config;
			if (!config.headers) config.headers = [];
			config.headers['Authorization'] = token;
			return config;
		};";
	}

	/**
	 * 
	 * */
	public function getUserManager() {
		return \lx::getModelManager($this->userModelName);
	}

	/**
	 * 
	 * */
	public function findUserByPassword($login, $password) {
		$manager = $this->getUserManager();

		$fields = (array)$this->userAuthenticateFields;
		foreach ($fields as $field) {
			$user = $manager->loadModel([
				$field => $login,
				$this->userPasswordField => $password,
			]);

			if ($user) {
				return $user;
			}
		}

		return null;
	}

	/**
	 *
	 * */
	public function registerUser($login, $password) {
		$userManager = $this->getUserManager();

		$user = $userManager->loadModel([$this->userLoginField => $login]);
		if ($user) {
			\lx::$dialog->addMessage("Login \"$login\" already exists");
			return false;
		}

		$user->email = $login;
		$user->password = $password;
		$user->save();
		return $user;
	}

	/**
	 *
	 * */
	public function updateAccessTokenForUser($user) {
		return $this->updateTokenForUser(
			$user,
			$this->getModelManager('AccessToken'),
			$this->getAccessTokenLifetime()
		);
	}

	/**
	 *
	 * */
	public function updateRefreshTokenForUser($user) {
		return $this->updateTokenForUser(
			$user,
			$this->getModelManager('RefreshToken'),
			$this->getRefreshTokenLifetime()
		);
	}

	/**
	 *
	 * */
	public function refreshTokens($refreshToken) {
		$arr = explode(' ', $refreshToken);
		if ($arr[0] != 'Bearer') {
			return false;
		} else {
			$refreshToken = $arr[1];
		}

		$refreshTokenManager = $this->getModelManager('RefreshToken');
		$refreshTokenModel = $refreshTokenManager->loadModel(['token' => $refreshToken]);
		if (!$refreshTokenModel) {
			return false;
		}

		$now = (new \DateTime())->format('Y-m-d H:i:s');
		$expire = (new \DateTime($refreshTokenModel->expire))->format('Y-m-d H:i:s');
		if ($expire <= $now) {
			\lx::$dialog->addMessage('expired');
			return false;
		}

		$manager = $this->getUserManager();
		$user = $manager->loadModel($refreshTokenModel->id_user);

		return [
			$this->updateAccessTokenForUser($user),
			$this->updateRefreshTokenForUser($user),
		];
	}

	/**
	 *
	 * */
	public static function logOut($user = null) {
		if ($user === null) {
			$user = \lx::$components->user;
			if ($user->isGuest()) {
				return;
			}
		}

		$time = new \DateTime();
		$time->modify('-5 minutes');
		$time = $time->format('Y-m-d H:i:s');

		$gate = \lx::$components->authenticationGate;

		$manager = $gate->getModelManager('RefreshToken');
		$token = $manager->loadModel(['id_user' => $user->id]);
		if ($token) {
			$token->expire = $time;
			$token->save();
		}

		$manager = $gate->getModelManager('AccessToken');
		$token = $manager->loadModel(['id_user' => $user->id]);
		if ($token) {
			$token->expire = $time;
			$token->save();
		}
	}


	/**************************************************************************************************************************
	 * PRIVATE
	 *************************************************************************************************************************/

	/**
	 * Ищем в данных запроса токен доступа
	 * */
	private function retrieveToken() {
		$token = null;

		$authHeader = \lx::$dialog->header('Authorization');
		if ($authHeader) {
			$token = $authHeader;
		}

		$authCookie = \lx::$dialog->cookie()->getFirstDefined(['auth', 'authorization', 'token'], false);
		if ($authCookie) {
			$token = $authCookie;
		}

		if ($token) {
			$arr = explode(' ', $token);
			if ($arr[0] != 'Bearer') {
				$token = null;
			} else {
				$token = $arr[1];
			}
		}

		return $token;
	}

	/**
	 *
	 * */
	private function updateTokenForUser($user, $manager, $lifetime) {
		$token = $manager->loadModel(['id_user' => $user->id]);
		if (!$token) {
			$token = $manager->newModel();
			$token->id_user = $user->id;
		}

		$token->token = $this->genTokenForUser($user);
		$expire = new \DateTime();
		$expire->modify('+' . $lifetime . ' seconds');
		$token->expire = $expire->format('Y-m-d H:i:s');
		$token->save();
		return $token;
	}

	/**
	 * 
	 * */
	private function getAccessTokenLifetime() {
		return $this->accessTokenLifetime;
	}

	/**
	 * 
	 * */
	private function getRefreshTokenLifetime() {
		return $this->refreshTokenLifetime;
	}

	/**
	 * 
	 * */
	private function genTokenForUser($user) {
		if ($this->tokenGenerator) {
			return $this->tokenGenerator->generate();
		} else {
			return md5('' . $user->id . time() . rand(0, PHP_INT_MAX));
		}
	}
}
