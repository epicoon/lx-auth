<?php

namespace lx\auth;

use lx\ApplicationToolTrait;
use lx\auth\models\AccessToken;
use lx\auth\models\RefreshToken;
use lx\ClassOfServiceInterface;
use lx\AuthenticationInterface;
use lx\EventListenerTrait;
use lx\FusionComponentInterface;
use lx\FusionComponentTrait;
use lx\ModelInterface;
use lx\ObjectTrait;
use lx\ResourceContext;
use lx\UserEventsEnum;
use lx\UserInterface;
use lx\UserManagerInterface;

/**
 * Class OAuth2AuthenticationGate
 * @package lx\auth
 */
class OAuth2AuthenticationGate implements AuthenticationInterface, FusionComponentInterface
{
    use ObjectTrait;
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

	protected $tokenServiceName = 'lx/auth';
	protected $checkTokenPlugin = 'lx/auth:getToken';
	protected $loginForm = 'lx.auth.LoginForm';

	private $authProblem;

	/**
	 * Принимает в конфигурации информацию о модели пользователя
	 * Первый способ:
	 * 'userModel' => 'some/service.modelName'
	 * Второй способ:
	 * 'userModel' => [
	 *        'service' => 'some/service',
	 *        'name' => 'modelName'
	 * ]
	 * */
	public function __construct(array $config = [])
	{
        $this->__objectConstruct($config);

		$this->authProblem = self::AUTH_PROBLEM_NO;
	}

	/**
	 * @return array
	 */
	public static function getEventHandlersMap()
	{
		return [
			UserEventsEnum::BEFORE_USER_DELETE => 'onUserDelete',
		];
	}

	/**
	 * @return string
	 */
	public function getLoginFormName()
	{
		return $this->loginForm;
	}


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * INTERFACE lx\AuthenticationInterface
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * Попытка аутентифицировать пользователя:
	 * - Проверяем данные запроса (заголовок Authorization, куки) - ищем токен доступа
	 * - Если токен не найден или закончился срок действия - компонент "пользователь" остается гостем
	 * - По токену ищем модель AccessToken
	 * - Если модель не найдена - компонент "пользователь" остается гостем
	 * - По полю логина модели ищем модель пользователя
	 * - Если модель пользователя не найдена - компонент "пользователь" остается гостем
	 * */
    public function authenticateUser(?array $authData = null): ?UserInterface
	{
	    if ($authData !== null) {
            $accessToken = $authData['accessToken'] ?? null;
            if ($accessToken) {
                $accessToken = preg_replace('/^Bearer /', '', $accessToken);
                return $this->authenticateUserByAccessToken($accessToken);
            }

            $refreshToken = $authData['refreshToken'] ?? null;
            if ($refreshToken) {
                $refreshToken = preg_replace('/^Bearer /', '', $refreshToken);
                return $this->authenticateUserByRefreshToken($refreshToken);
            }

            return null;
        }

        $accessToken = $this->retrieveToken();
        if (!$accessToken) {
            $this->authProblem = self::AUTH_PROBLEM_TOKEN_NOT_RETRIEVED;
            return null;
        }

        return $this->authenticateUserByAccessToken($accessToken, $this->app->user);
	}

	/**
	 * Сформировать ответ, пытающийся авторизовать пользователя
	 * */
	public function responseToAuthenticate()
	{
		switch ($this->authProblem) {
			// Группа проблем, где можно инициировать аутентификацию
			case self::AUTH_PROBLEM_TOKEN_NOT_RETRIEVED:
			case self::AUTH_PROBLEM_TOKEN_EXPIRED:
				// Послать плагин, который поищет токен доступа
				$arr = explode(':', $this->checkTokenPlugin);
				return new ResourceContext([
					'service' => $arr[0],
					'plugin' => $arr[1],
				]);

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

    /**
     * @return int
     */
	public function getProblemCode()
    {
        return $this->authProblem;
    }


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PUBLIC
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    public function isTokenNotFound()
    {
        return $this->authProblem == self::AUTH_PROBLEM_TOKEN_NOT_FOUND;
    }

    public function isTokenExpired()
    {
        return $this->authProblem == self::AUTH_PROBLEM_TOKEN_EXPIRED;
    }
    
    public function updateAccessTokenForUser($user)
	{
		return $this->updateTokenForUser(
			$user,
			AccessToken::class,
			$this->getAccessTokenLifetime()
		);
	}

	public function updateRefreshTokenForUser($user)
	{
		return $this->updateTokenForUser(
			$user,
			RefreshToken::class,
			$this->getRefreshTokenLifetime()
		);
	}

    /**
     * @param string $refreshToken
     * @return array|null
     * @throws \Exception
     */
	public function refreshTokens($refreshToken)
	{
		$arr = explode(' ', $refreshToken);
		if ($arr[0] != 'Bearer') {
			return null;
		} else {
			$refreshToken = $arr[1];
		}

        $refreshTokenModel = RefreshToken::findOne(['token' => $refreshToken]);
		if (!$refreshTokenModel) {
			return null;
		}
		
		$now = (new \DateTime())->format('Y-m-d H:i:s');
		$expire = (new \DateTime($refreshTokenModel->expire))->format('Y-m-d H:i:s');
		if ($expire <= $now) {
			$this->authProblem = self::AUTH_PROBLEM_TOKEN_EXPIRED;
			return null;
		}

		/** @var UserManagerInterface $userManager */
		$userManager = $this->app->userManager;
		$user = $userManager->identifyUserByAuthValue($refreshTokenModel->userAuthValue);
		if (!$user) {
			$this->authProblem = self::AUTH_PROBLEM_USER_NOT_FOUND;
			return null;
		}

		return [
			$this->updateAccessTokenForUser($user),
			$this->updateRefreshTokenForUser($user),
		];
	}

	public function logOut(?UserInterface $user = null)
	{
		if ($user === null) {
			$user = $this->app->user;
		}

        if ($user->isGuest()) {
            return;
        }

		$time = (new \DateTime())->modify('-5 minutes')->format('Y-m-d H:i:s');

        $accessToken = AccessToken::findOne(['userAuthValue' => $user->getAuthValue()]);
        $accessToken->expire = $time;

        $refreshToken = RefreshToken::findOne(['userAuthValue' => $user->getAuthValue()]);
        $refreshToken->expire = $time;

        $accessToken->getRepository()->hold();
        $accessToken->save();
        $refreshToken->save();
        $accessToken->getRepository()->commit();
	}


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PROTECTED
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * Получить менеджер моделей сервиса для текущего класса
	 *
	 * @param $modelName string
	 * @return \lx\model\ModelManager|null
	 */
	protected function getModelManager($modelName)
	{
		$service = $this->app->getService($this->tokenServiceName);
		if (!$service) {
			return null;
		}

		return $service->getModelManager($modelName);
	}


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private function authenticateUserByAccessToken(string $token, ?UserInterface $defaultUser = null): ?UserInterface
    {
        return $this->authenticateUserByToken(AccessToken::class, $token, $defaultUser);
    }

    private function authenticateUserByRefreshToken(string $token, ?UserInterface $defaultUser = null): ?UserInterface
    {
        return $this->authenticateUserByToken(RefreshToken::class, $token, $defaultUser);
    }

    /**
     * @param string&AccessToken&RefreshToken $tokenClass
     * @param string $token
     * @param UserInterface|null $defaultUser
     * @return UserInterface|null
     * @throws \Exception
     */
    private function authenticateUserByToken(
        string $tokenClass,
        string $token,
        ?UserInterface $defaultUser = null
    ): ?UserInterface
    {
        $tokenModel = $tokenClass::findOne(['token' => $token]);
        if (!$tokenModel) {
            \lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Auth token '$token' not found",
            ]);

            $this->authProblem = self::AUTH_PROBLEM_TOKEN_NOT_FOUND;
            return null;
        }

        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $expire = (new \DateTime($tokenModel->expire))->format('Y-m-d H:i:s');
        if ($expire <= $now) {
            $this->authProblem = self::AUTH_PROBLEM_TOKEN_EXPIRED;
            return null;
        }

        /** @var UserManagerInterface $userManager */
        $userManager = $this->app->userManager;
        $user = $userManager->identifyUserByAuthValue($tokenModel->userAuthValue, $defaultUser);
        if (!$user) {
            $this->authProblem = self::AUTH_PROBLEM_USER_NOT_FOUND;
            return null;
        }

        return $user;
    }

	/**
	 * Ищем в данных запроса токен доступа
	 *
	 * @return string|null
	 */
	private function retrieveToken()
	{
		$token = null;

		$authHeader = $this->app->dialog->getHeader('Authorization');
		if ($authHeader) {
			$token = $authHeader;
		}

		if ($token === null) {
            $authCookie = $this->app->dialog->getCookie()->getFirstDefined(
                ['auth', 'authorization', 'token'],
                false
            );
            if ($authCookie) {
                $token = $authCookie;
            }
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
     * @param UserInterface $user
     * @param string&AccessToken&RefreshToken $tokenClass
     * @param int $lifetime
     * @return AccessToken|RefreshToken
     */
	private function updateTokenForUser($user, $tokenClass, $lifetime)
	{
	    /** @var AccessToken|RefreshToken $token */
	    $token = $tokenClass::findOne([
	        'userAuthValue' => $user->getAuthValue(),
        ]);
        if (!$token) {
            $token = new $tokenClass([
                'userAuthValue' => $user->getAuthValue(),
            ]);
        }

        $token->token = $this->genTokenForUser($user);
        $expire = new \DateTime();
        $expire->modify('+' . $lifetime . ' seconds');
        $token->expire = $expire->format('Y-m-d H:i:s');
        $token->save();
        return $token;
	}

	private function getAccessTokenLifetime()
	{
		return $this->accessTokenLifetime;
	}

	private function getRefreshTokenLifetime()
	{
		return $this->refreshTokenLifetime;
	}

    /**
     * @param UserInterface&ModelInterface $user
     * @return string
     */
	private function genTokenForUser($user)
	{
		if ($this->tokenGenerator) {
			return $this->tokenGenerator->generate();
		} else {
			return md5('' . $user->getId() . time() . rand(0, PHP_INT_MAX));
		}
	}


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * EVENT HANDLERS
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * @param UserInterface $user
     */
	private function onUserDelete($user)
	{
		$userAuthValue = $user->getAuthValue();
		RefreshToken::deleteAll(['userAuthValue' => $userAuthValue]);
        AccessToken::deleteAll(['userAuthValue' => $userAuthValue]);
	}
}
