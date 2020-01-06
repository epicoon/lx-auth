<?php

namespace lx\auth;

use lx\Rect;

/**
 * Форма логина
 * */
class LoginForm extends Rect {
	protected static function ajaxMethods() {
		return [
			'login',
			'register',
		];
	}

	public function login($login, $password) {
		$gate = $this->app->authenticationGate;

		$user = $gate->findUserByPassword($login, $password);
		if (!$user) {
			return [
				'success' => false,
				'message' => 'User not found',
			];
		}
		
		$accessTokenModel = $gate->updateAccessTokenForUser($user);
		$refreshTokenModel = $gate->updateRefreshTokenForUser($user);
		return [
			'success' => true,
			'token' => 'Bearer ' . $accessTokenModel->token,
			'refreshToken' => 'Bearer ' . $refreshTokenModel->token,
		];
	}

	public function register($login, $password) {
		$gate = $this->app->authenticationGate;

		$user = $gate->registerUser($login, $password);
		if (!$user) {
			return [
				'success' => false,
				'message' => "Login \"$login\" already exists",
			];
		}

		$accessTokenModel = $gate->updateAccessTokenForUser($user);
		$refreshTokenModel = $gate->updateRefreshTokenForUser($user);
		return [
			'success' => true,
			'token' => 'Bearer ' . $accessTokenModel->token,
			'refreshToken' => 'Bearer ' . $refreshTokenModel->token,
		];
	}
}
