<?php

namespace lx\auth;

use lx\Rect;
use lx\ResponseCodeEnum;

/**
 * Форма логина
 * */
class LoginForm extends Rect
{
	public function login($login, $password)
    {
		$processor = $this->app->userProcessor;

		$user = $processor->findUserByPassword($login, $password);
		if ( ! $user) {

		    return $this->prepareErrorResponse(
		        'User not found',
                ResponseCodeEnum::NOT_FOUND,
            );
		}

		$gate = $this->app->authenticationGate;
		$accessTokenModel = $gate->updateAccessTokenForUser($user);
		$refreshTokenModel = $gate->updateRefreshTokenForUser($user);

		return $this->prepareResponse([
            'token' => 'Bearer ' . $accessTokenModel->token,
            'refreshToken' => 'Bearer ' . $refreshTokenModel->token,
        ]);
    }

	public function register($login, $password)
    {
		$processor = $this->app->userProcessor;
		if ( ! $processor) {
            \lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => 'User processor does not exist',
            ]);

            return $this->prepareErrorResponse('Internal server error');
		}

		$user = $processor->createUser($login, $password);
		if ( ! $user) {

		    return $this->prepareErrorResponse("Login \"$login\" already exists");
		}

		$gate = $this->app->authenticationGate;
		$accessTokenModel = $gate->updateAccessTokenForUser($user);
		$refreshTokenModel = $gate->updateRefreshTokenForUser($user);

		return $this->prepareResponse([
            'token' => 'Bearer ' . $accessTokenModel->token,
            'refreshToken' => 'Bearer ' . $refreshTokenModel->token,
        ]);
    }
}
