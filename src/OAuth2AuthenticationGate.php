<?php

namespace lx\auth;

use lx;
use lx\auth\models\AccessToken;
use lx\auth\models\RefreshToken;
use lx\AuthenticationInterface;
use lx\EventListenerTrait;
use lx\FusionComponentInterface;
use lx\FusionComponentTrait;
use lx\ModelInterface;
use lx\ResourceContext;
use lx\UserEventsEnum;
use lx\UserInterface;
use lx\UserManagerInterface;

/**
 * Configuration has to have user model information:
 * The first way:
 * 'userModel' => 'some/service.ModelName'
 * The second way:
 * 'userModel' => [
 *        'service' => 'some/service',
 *        'name' => 'ModelName'
 * ]
 */
class OAuth2AuthenticationGate implements AuthenticationInterface, FusionComponentInterface
{
	use FusionComponentTrait;
	use EventListenerTrait;

	const AUTH_PROBLEM_NO = 0;
	const AUTH_PROBLEM_USER_COMPONENT_IS_UNAVAILABLE = 5;
	const AUTH_PROBLEM_TOKEN_NOT_RETRIEVED = 10;
	const AUTH_PROBLEM_TOKEN_NOT_FOUND = 15;
	const AUTH_PROBLEM_TOKEN_EXPIRED = 20;
	const AUTH_PROBLEM_USER_NOT_FOUND = 25;

	protected int $accessTokenLifetime = 300;
	protected int $refreshTokenLifetime = 84600;

	//TODO
	protected $tokenGenerator = null;

	protected string $tokenServiceName = 'lx/auth';
	protected string $checkTokenPlugin = 'lx/auth:getToken';
	protected string $loginForm = 'lx.auth.LoginForm';
    protected array $jsModules = ['lx.auth.AjaxAuthHandler'];

	private int $authProblem;

	protected function init(): void
	{
		$this->authProblem = self::AUTH_PROBLEM_NO;
	}

	public static function getEventHandlersMap(): array
	{
		return [
			UserEventsEnum::BEFORE_USER_DELETE => 'onUserDelete',
		];
	}

	public function getLoginFormName(): string
	{
		return $this->loginForm;
	}


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * INTERFACE lx\AuthenticationInterface
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * Try to authenticate user:
	 * - Request checking (header Authorization, cookie) - find for access token
	 * - If token not found, the user component stay guest
	 * - By token find model AccessToken
	 * - If model not found, the user component stay guest
	 * - By AccessToken model auth-value find user model
     * - If model not found, the user component stay guest
	 */
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

        return $this->authenticateUserByAccessToken($accessToken, lx::$app->user);
	}

	public function responseToAuthenticate(): ?ResourceContext
	{
		switch ($this->authProblem) {
			case self::AUTH_PROBLEM_TOKEN_NOT_RETRIEVED:
			case self::AUTH_PROBLEM_TOKEN_EXPIRED:
				// Послать плагин, который поищет токен доступа
				$arr = explode(':', $this->checkTokenPlugin);
				return new ResourceContext([
					'service' => $arr[0],
					'plugin' => $arr[1],
				]);

            // Authirization limit
            case self::AUTH_PROBLEM_NO:
            // Application hasn't user component
            case self::AUTH_PROBLEM_USER_COMPONENT_IS_UNAVAILABLE:
            // Recieved token isn't exist
            case self::AUTH_PROBLEM_TOKEN_NOT_FOUND:
            // User wasn't found by token
            case self::AUTH_PROBLEM_USER_NOT_FOUND:
                return null;
        }

		return null;
	}

	public function getProblemCode(): int
    {
        return $this->authProblem;
    }

    public function getJsModules(): array
    {
        return $this->jsModules;
    }


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PUBLIC
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    public function isTokenNotFound(): bool
    {
        return $this->authProblem == self::AUTH_PROBLEM_TOKEN_NOT_FOUND;
    }

    public function isTokenExpired(): bool
    {
        return $this->authProblem == self::AUTH_PROBLEM_TOKEN_EXPIRED;
    }
    
    public function updateAccessTokenForUser(UserInterface $user): AccessToken
	{
		return $this->updateTokenForUser(
			$user,
			AccessToken::class,
			$this->getAccessTokenLifetime()
		);
	}

	public function updateRefreshTokenForUser(UserInterface $user): RefreshToken
	{
		return $this->updateTokenForUser(
			$user,
			RefreshToken::class,
			$this->getRefreshTokenLifetime()
		);
	}

    /**
     * @throws \Exception
     */
	public function refreshTokens(string $refreshToken): ?array
	{
		$arr = explode(' ', $refreshToken);
		if ($arr[0] != 'Bearer') {
			return null;
		} else {
			$refreshToken = $arr[1];
		}

        $refreshTokenModel = RefreshToken::findOne(['token' => $refreshToken], false);
		if (!$refreshTokenModel) {
			return null;
		}
		
		$now = (new \DateTime())->format('Y-m-d H:i:s');
		$expire = (new \DateTime($refreshTokenModel->expire))->format('Y-m-d H:i:s');
		if ($expire <= $now) {
			$this->authProblem = self::AUTH_PROBLEM_TOKEN_EXPIRED;
			return null;
		}

		$userManager = lx::$app->userManager;
		$user = $userManager->identifyUserByAuthValue($refreshTokenModel->userAuthValue);
		if (!$user) {
			$this->authProblem = self::AUTH_PROBLEM_USER_NOT_FOUND;
			return null;
		}

        $accessToken = $this->updateAccessTokenForUser($user);
        $refreshToken = $this->updateRefreshTokenForUser($user);
		return [
            'user' => $user,
            'accessTokenModel' => $accessToken,
            'refreshTokenModel' => $refreshToken,
            'accessToken' => 'Bearer ' . $accessToken->token,
            'refreshToken' => 'Bearer ' . $refreshToken->token,
		];
	}

	public function logOut(?UserInterface $user = null): void
	{
		if ($user === null) {
			$user = lx::$app->user;
		}

        if ($user->isGuest()) {
            return;
        }

		$time = (new \DateTime())->modify('-5 minutes')->format('Y-m-d H:i:s');

        $accessToken = AccessToken::findOne(['userAuthValue' => $user->getAuthValue()], false);
        $accessToken->expire = $time;

        $refreshToken = RefreshToken::findOne(['userAuthValue' => $user->getAuthValue()], false);
        $refreshToken->expire = $time;

        $accessToken->getRepository()->hold();
        $accessToken->save();
        $refreshToken->save();
        $accessToken->getRepository()->commit();
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
     * @throws \Exception
     */
    private function authenticateUserByToken(
        string $tokenClass,
        string $token,
        ?UserInterface $defaultUser = null
    ): ?UserInterface
    {
        $tokenModel = $tokenClass::findOne(['token' => $token], false);
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

        $userManager = lx::$app->userManager;
        $user = $userManager->identifyUserByAuthValue($tokenModel->userAuthValue, $defaultUser);
        if (!$user) {
            $this->authProblem = self::AUTH_PROBLEM_USER_NOT_FOUND;
            return null;
        }

        return $user;
    }

	private function retrieveToken(): ?string
	{
		$token = null;

		$authHeader = lx::$app->request->getHeader('Authorization');
		if ($authHeader) {
			$token = $authHeader;
		}

		if ($token === null) {
            $authCookie = lx::$app->request->getCookie()->getFirstDefined(
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
     * @param string&AccessToken&RefreshToken $tokenClass
     * @return AccessToken|RefreshToken
     */
	private function updateTokenForUser(UserInterface $user, string $tokenClass, int $lifetime)
	{
	    /** @var AccessToken|RefreshToken $token */
	    $token = $tokenClass::findOne([
	        'userAuthValue' => $user->getAuthValue(),
        ], false);
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

	private function getAccessTokenLifetime(): int
	{
		return $this->accessTokenLifetime;
	}

	private function getRefreshTokenLifetime(): int
	{
		return $this->refreshTokenLifetime;
	}

    /**
     * @param UserInterface&ModelInterface $user
     */
	private function genTokenForUser(UserInterface $user): string
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

	private function onUserDelete(UserInterface $user): void
	{
		$userAuthValue = $user->getAuthValue();
		RefreshToken::deleteAll(['userAuthValue' => $userAuthValue]);
        AccessToken::deleteAll(['userAuthValue' => $userAuthValue]);
	}
}
