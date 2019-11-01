<?php

namespace lx\auth;

use lx\ApplicationComponent;
use lx\ClassOfServiceInterface;
use lx\AuthenticationInterface;

/**
 *
 * */
class OAuth2AuthenticationGate extends ApplicationComponent implements AuthenticationInterface {
	const AUTH_PROBLEM_NO = 0;
	const AUTH_PROBLEM_TOKEN_NOT_RETRIEVED = 5;
	const AUTH_PROBLEM_TOKEN_NOT_FOUND = 10;
	const AUTH_PROBLEM_TOKEN_EXPIRED = 15;
	const AUTH_PROBLEM_USER_NOT_FOUND = 20;

	protected $userAuthFields = 'login';
	protected $userAuthField = 'login';
	protected $userPasswordField = 'password';
	protected $accessTokenLifetime = 300;
	protected $refreshTokenLifetime = 84600;
	protected $tokenGenerator = null;

	protected $tokenServiceName = 'lx/lx-auth';
	protected $checkTokenPlugin = 'lx/lx-auth:getToken';
	protected $loginForm = 'lx.auth.LoginForm';

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


	/**************************************************************************************************************************
	 * INTERFACE lx\AuthenticationInterface
	 *************************************************************************************************************************/

	/**
	 * Попытка аутентифицировать пользователя:
	 * - Проверяем данные запроса (заголовок Authorization, куки) - ищем токен доступа
	 * - Если токен не найден или закончился срок действия - компонент "пользователь" остается гостем
	 * - По токену ищем модель AccessToken
	 * - Если модель не найдена - компонент "пользователь" остается гостем
	 * - По полю логина модели ищем модель пользователя
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
			$this->app->dialog->addMessage('expired');
			$this->authProblem = self::AUTH_PROBLEM_TOKEN_EXPIRED;
			return;
		}

		$userManager = $this->getUserManager();
		$user = $userManager->loadModel([$this->userAuthField => $accessTokenModel->user_login]);
		if (!$user) {
			$this->authProblem = self::AUTH_PROBLEM_USER_NOT_FOUND;
			return;
		}

		$this->app->user->setData($user);
		$this->app->user->setAuthFieldName($this->userAuthField);
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
				$arr = explode(':', $this->checkTokenPlugin);
				$service  = $arr[0];
				$plugin = $arr[1];
				$data = [
					'service' => $service,
					'plugin' => $plugin,
				];
				if ($this->loginForm) {
					$data['clientParams'] = [
						'loginForm' => $this->loginForm,
					];
					$data['dependencies'] = [
						'modules' => [$this->loginForm],
					];
				}

				$responseSource->setData($data);
				break;

			// Группа проблем, не разрешающихся аутентификацией
			case self::AUTH_PROBLEM_NO:
				// Проблем при аутентификации не было, но раз наложены ограничения при авторизации - доступа нет
				return false;
			case self::AUTH_PROBLEM_TOKEN_NOT_FOUND:
				// Токен мы получили, но в своей системе не нашли - подозрительная ситуация
				return false;
			case self::AUTH_PROBLEM_USER_NOT_FOUND:
				// Токен получили, нашли, но не нашли по нему пользователя - возможно ошибка базы
				return false;
		}

		return $responseSource;
	}

	/**
	 * Js-расширение для клиента
	 * */
	public function getJs() {
		return "lx.__auth = function(request){
			let token = lx.Storage.get('lxauthtoken');
			if (!token) return;
			request.setRequestHeader('Authorization', token);
		};";
	}


	/**************************************************************************************************************************
	 * PUBLIC
	 *************************************************************************************************************************/

	/**
	 * 
	 * */
	public function getUserManager() {
		return $this->app->getModelManager($this->userModelName);
	}

	/**
	 * 
	 * */
	public function findUserByPassword($login, $password) {
		$manager = $this->getUserManager();

		$fields = (array)$this->userAuthFields;
		foreach ($fields as $field) {
			$user = $manager->loadModel([
				$field => $login,
				$this->userPasswordField => $this->getPasswordHash($password),
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

		$user = $userManager->loadModel([$this->userAuthField => $login]);
		if ($user) {
			$this->app->dialog->useMessages();
			$this->app->dialog->addMessage("Login \"$login\" already exists");
			return false;
		}

		$user = $userManager->newModel();
		$user->{$this->userAuthField} = $login;
		$user->{$this->userPasswordField} = $this->getPasswordHash($password);
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
			$this->app->dialog->addMessage('expired');
			return false;
		}

		$manager = $this->getUserManager();
		$user = $manager->loadModel([$this->userAuthField => $refreshTokenModel->user_login]);

		return [
			$this->updateAccessTokenForUser($user),
			$this->updateRefreshTokenForUser($user),
		];
	}

	/**
	 *
	 * */
	public function logOut($user = null) {
		if ($user === null) {
			$user = $this->app->user;
			if ($user->isGuest()) {
				return;
			}
		}

		$time = new \DateTime();
		$time->modify('-5 minutes');
		$time = $time->format('Y-m-d H:i:s');

		$manager = $this->getModelManager('RefreshToken');
		$token = $manager->loadModel(['user_login' => $user->{$this->userAuthField}]);
		if ($token) {
			$token->expire = $time;
			$token->save();
		}

		$manager = $this->getModelManager('AccessToken');
		$token = $manager->loadModel(['user_login' => $user->{$this->userAuthField}]);
		if ($token) {
			$token->expire = $time;
			$token->save();
		}
	}


	/**************************************************************************************************************************
	 * PROTECTED
	 *************************************************************************************************************************/

	/**
	 *
	 * */
	protected function getPasswordHash($password) {
		$options = [
			'salt' => md5($password),
			'cost' => 12,
		];
		return password_hash($password, PASSWORD_DEFAULT, $options);
	}

	/**
	 * Получить менеджер моделей сервиса для текущего класса
	 *
	 * @param $modelName string
	 * @return lx\ModelManager|null
	 * */
	protected function getModelManager($modelName) {
		$service = $this->app->getService($this->tokenServiceName);
		if (!$service) {
			return null;
		}

		return $service->getModelManager($modelName);
	}


	/**************************************************************************************************************************
	 * PRIVATE
	 *************************************************************************************************************************/

	/**
	 * Ищем в данных запроса токен доступа
	 * */
	private function retrieveToken() {
		$token = null;

		$authHeader = $this->app->dialog->header('Authorization');
		if ($authHeader) {
			$token = $authHeader;
		}

		$authCookie = $this->app->dialog->cookie()->getFirstDefined(['auth', 'authorization', 'token'], false);
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
		$token = $manager->loadModel(['user_login' => $user->{$this->userAuthField}]);
		if (!$token) {
			$token = $manager->newModel();
			$token->user_login = $user->{$this->userAuthField};
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
