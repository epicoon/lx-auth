<?php

namespace lx\auth;

use lx\ApplicationToolTrait;
use lx\ClassOfServiceInterface;
use lx\AuthenticationInterface;
use lx\EventListenerTrait;
use lx\FusionComponentInterface;
use lx\FusionComponentTrait;
use lx\Object;
use lx\SourceContext;
use lx\UserEventsEnum;

/**
 * Class OAuth2AuthenticationGate
 * @package lx\auth
 */
class OAuth2AuthenticationGate extends Object implements AuthenticationInterface, FusionComponentInterface {
	use ApplicationToolTrait;
	use FusionComponentTrait;
	use EventListenerTrait;

	const AUTH_PROBLEM_NO = 0;
	const AUTH_PROBLEM_USER_COMPONENT_IS_UNAVAILABLE = 5;
	const AUTH_PROBLEM_TOKEN_NOT_RETRIEVED = 10;
	const AUTH_PROBLEM_TOKEN_NOT_FOUND = 15;
	const AUTH_PROBLEM_TOKEN_EXPIRED = 20;
	const AUTH_PROBLEM_USER_NOT_FOUND = 25;

	protected $accessTokenLifetime = 300;
	protected $refreshTokenLifetime = 84600;
	protected $tokenGenerator = null;

	protected $tokenServiceName = 'lx/lx-auth';
	protected $checkTokenPlugin = 'lx/lx-auth:getToken';
	protected $loginForm = 'lx.auth.LoginForm';

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
	}

	public static function getEventHandlersMap()
	{
		return [
			UserEventsEnum::BEFORE_USER_DELETE => 'onUserDelete',
		];
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
		if ( ! $this->app->user->isAvailable()) {
			$this->authProblem = self::AUTH_PROBLEM_USER_COMPONENT_IS_UNAVAILABLE;
			return false;
		}

		$accessToken = $this->retrieveToken();
		if (!$accessToken) {
			$this->authProblem = self::AUTH_PROBLEM_TOKEN_NOT_RETRIEVED;
			return false;
		}

		$accessTokenManager = $this->getModelManager('AccessToken');
		$accessTokenModel = $accessTokenManager->loadModel(['token' => $accessToken]);
		if (!$accessTokenModel) {
			$this->authProblem = self::AUTH_PROBLEM_TOKEN_NOT_FOUND;
			return false;
		}

		$now = (new \DateTime())->format('Y-m-d H:i:s');
		$expire = (new \DateTime($accessTokenModel->expire))->format('Y-m-d H:i:s');
		if ($expire <= $now) {
			$this->authProblem = self::AUTH_PROBLEM_TOKEN_EXPIRED;
			return false;
		}

		$appUserSuccess = $this->app->userProcessor->setApplicationUser($accessTokenModel->user_login);
		if ($appUserSuccess) {
			return true;
		} else {
			$this->authProblem = self::AUTH_PROBLEM_USER_NOT_FOUND;
			return false;
		}
	}
	
	public function tokenIsExpired()
	{
		return $this->authProblem = self::AUTH_PROBLEM_TOKEN_EXPIRED;
	}

	/**
	 * Сформировать ответ, пытающийся авторизовать пользователя
	 * */
	public function responseToAuthenticate() {
		switch ($this->authProblem) {
			// Группа проблем, где можно инициировать аутентификацию
			case self::AUTH_PROBLEM_TOKEN_NOT_RETRIEVED:
			case self::AUTH_PROBLEM_TOKEN_EXPIRED:
				// Послать плагин, который поищет токен доступа
				$arr = explode(':', $this->checkTokenPlugin);
				$service  = $arr[0];
				$plugin = $arr[1];
				$data = [
					'service' => $service,
					'plugin' => $plugin,
					'method' => 'build',
				];
				if ($this->loginForm) {
					$data['clientParams'] = [
						'loginForm' => $this->loginForm,
					];
					$data['dependencies'] = [
						'modules' => [$this->loginForm],
					];
				}

				return new SourceContext($data);

			// Группа проблем, не разрешающихся аутентификацией
			case self::AUTH_PROBLEM_NO:
				// Проблем при аутентификации не было, но раз наложены ограничения при авторизации - доступа нет
				return false;
			case self::AUTH_PROBLEM_USER_COMPONENT_IS_UNAVAILABLE:
				// Компонент приложения "пользователь" не сконфигурирован
				return false;
			case self::AUTH_PROBLEM_TOKEN_NOT_FOUND:
				// Токен мы получили, но в своей системе не нашли - подозрительная ситуация
				return false;
			case self::AUTH_PROBLEM_USER_NOT_FOUND:
				// Токен получили, нашли, но не нашли по нему пользователя - возможно ошибка базы
				return false;
		}

		return false;
	}


	/**************************************************************************************************************************
	 * PUBLIC
	 *************************************************************************************************************************/

	public function updateAccessTokenForUser($user) {
		return $this->updateTokenForUser(
			$user,
			$this->getModelManager('AccessToken'),
			$this->getAccessTokenLifetime()
		);
	}

	public function updateRefreshTokenForUser($user) {
		return $this->updateTokenForUser(
			$user,
			$this->getModelManager('RefreshToken'),
			$this->getRefreshTokenLifetime()
		);
	}

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
			$this->authProblem = self::AUTH_PROBLEM_TOKEN_EXPIRED;
			return false;
		}

		$user = $this->app->userProcessor->getUser($refreshTokenModel->user_login);
		if ( ! $user) {
			$this->authProblem = self::AUTH_PROBLEM_USER_NOT_FOUND;
			return false;
		}

		return [
			$this->updateAccessTokenForUser($user),
			$this->updateRefreshTokenForUser($user),
		];
	}

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
		$token = $manager->loadModel(['user_login' => $user->getAuthField()]);
		if ($token) {
			$token->expire = $time;
			$token->save();
		}

		$manager = $this->getModelManager('AccessToken');
		$token = $manager->loadModel(['user_login' => $user->getAuthField()]);
		if ($token) {
			$token->expire = $time;
			$token->save();
		}
	}


	/*******************************************************************************************************************
	 * PROTECTED
	 ******************************************************************************************************************/

	/**
	 * Получить менеджер моделей сервиса для текущего класса
	 *
	 * @param $modelName string
	 * @return \lx\model\ModelManager|null
	 */
	protected function getModelManager($modelName) {
		$service = $this->app->getService($this->tokenServiceName);
		if (!$service) {
			return null;
		}

		return $service->getModelManager($modelName);
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * Ищем в данных запроса токен доступа
	 *
	 * @return string|null
	 */
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

	private function updateTokenForUser($user, $manager, $lifetime) {
		$token = $manager->loadModel(['user_login' => $user->getAuthField()]);
		if (!$token) {
			$token = $manager->newModel();
			$token->user_login = $user->getAuthField();
		}

		$token->token = $this->genTokenForUser($user);
		$expire = new \DateTime();
		$expire->modify('+' . $lifetime . ' seconds');
		$token->expire = $expire->format('Y-m-d H:i:s');
		$token->save();
		return $token;
	}

	private function getAccessTokenLifetime() {
		return $this->accessTokenLifetime;
	}

	private function getRefreshTokenLifetime() {
		return $this->refreshTokenLifetime;
	}

	private function genTokenForUser($user) {
		if ($this->tokenGenerator) {
			return $this->tokenGenerator->generate();
		} else {
			return md5('' . $user->id . time() . rand(0, PHP_INT_MAX));
		}
	}


	/*******************************************************************************************************************
	 * EVENT HANDLERS
	 ******************************************************************************************************************/

	private function onUserDelete($user)
	{
		$authValue = $user->getAuthField();

		$manager = $this->getModelManager('RefreshToken');
		$manager->deleteModelsByCondition([
			'user_login' => $authValue
		]);

		$manager = $this->getModelManager('AccessToken');
		$manager->deleteModelsByCondition([
			'user_login' => $authValue
		]);
	}
}
