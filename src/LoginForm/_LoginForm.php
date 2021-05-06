<?php

namespace lx\auth;

use lx\AuthenticationInterface;
use lx\Rect;
use lx\ResponseCodeEnum;
use lx\ResponseInterface;
use lx\UserManagerInterface;

class LoginForm extends Rect
{
	public function login(string $login, string $password): ResponseInterface
    {
        /** @var UserManagerInterface $userManager */
		$userManager = $this->app->userManager;

		$user = $userManager->identifyUserByPassword($login, $password);
		if (!$user) {
		    return $this->prepareErrorResponse(
		        'User not found',
                ResponseCodeEnum::NOT_FOUND,
            );
		}

        /** @var AuthenticationInterface $gate */
		$gate = $this->app->authenticationGate;
		$accessTokenModel = $gate->updateAccessTokenForUser($user);
		$refreshTokenModel = $gate->updateRefreshTokenForUser($user);

		return $this->prepareResponse([
            'token' => 'Bearer ' . $accessTokenModel->token,
            'refreshToken' => 'Bearer ' . $refreshTokenModel->token,
        ]);
    }

	public function register(string $login, string $password): ResponseInterface
    {
        /** @var UserManagerInterface $userManager */
		$userManager = $this->app->userManager;
		if ( ! $userManager) {
            \lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => 'User manager does not exist',
            ]);

            return $this->prepareErrorResponse('Internal server error');
		}

		$user = $userManager->createUser($login, $password);
		if (!$user) {
		    return $this->prepareErrorResponse("Login \"$login\" already exists");
		}

		/** @var AuthenticationInterface $gate */
		$gate = $this->app->authenticationGate;
		$accessTokenModel = $gate->updateAccessTokenForUser($user);
		$refreshTokenModel = $gate->updateRefreshTokenForUser($user);

		return $this->prepareResponse([
            'token' => 'Bearer ' . $accessTokenModel->token,
            'refreshToken' => 'Bearer ' . $refreshTokenModel->token,
        ]);
    }
}
