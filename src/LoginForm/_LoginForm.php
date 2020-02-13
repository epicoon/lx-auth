<?php

namespace lx\auth;

use lx\Rect;

/**
 * Форма логина
 * */
class LoginForm extends Rect {
	public function login($login, $password) {
		$processor = $this->app->userProcessor;

		$user = $processor->findUserByPassword($login, $password);
		if ( ! $user) {
			return [
				'success' => false,
				'message' => 'User not found',
			];
		}

		$gate = $this->app->authenticationGate;
		$accessTokenModel = $gate->updateAccessTokenForUser($user);
		$refreshTokenModel = $gate->updateRefreshTokenForUser($user);
		return [
			'success' => true,
			'token' => 'Bearer ' . $accessTokenModel->token,
			'refreshToken' => 'Bearer ' . $refreshTokenModel->token,
		];
	}

	public function register($login, $password) {
		$processor = $this->app->userProcessor;
		if ( ! $processor) {
			return [
				'success' => false,
				'message' => 'User processor does not exist',
			];
		}

		$user = $processor->createUser($login, $password);
		if ( ! $user) {
			return [
				'success' => false,
				'message' => "Login \"$login\" already exists",
			];
		}

		$gate = $this->app->authenticationGate;
		$accessTokenModel = $gate->updateAccessTokenForUser($user);
		$refreshTokenModel = $gate->updateRefreshTokenForUser($user);
		return [
			'success' => true,
			'token' => 'Bearer ' . $accessTokenModel->token,
			'refreshToken' => 'Bearer ' . $refreshTokenModel->token,
		];
	}
}
