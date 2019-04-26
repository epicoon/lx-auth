<?php

namespace lx\auth;

use lx\Box;

/**
 * Форма логина
 * */
class LoginForm extends Box {
	/**
	 *
	 * */
	public function __construct($config = []) {
		parent::__construct($config);
	}

	/**
	 *
	 * */
	public static function login($login, $password) {
		$gate = \lx::$components->authenticationGate;

		$user = $gate->findUserByPassword($login, $password);
		if (!$user) {
			return [
				'result' => false,
				'message' => 'User not found',
			];
		}
		
		$accessTokenModel = $gate->updateAccessTokenForUser($user);
		$refreshTokenModel = $gate->updateRefreshTokenForUser($user);
		return [
			'result' => true,
			'token' => 'Bearer ' . $accessTokenModel->token,
			'refreshToken' => 'Bearer ' . $refreshTokenModel->token,
		];
	}

	/**
	 *
	 * */
	public static function register($login, $password) {
		$gate = \lx::$components->authenticationGate;

		$user = $gate->registerUser($login, $password);
		if (!$user) {
			return [
				'result' => false,
			];
		}

		$accessTokenModel = $gate->updateAccessTokenForUser($user);
		$refreshTokenModel = $gate->updateRefreshTokenForUser($user);
		return [
			'result' => true,
			'token' => 'Bearer ' . $accessTokenModel->token,
			'refreshToken' => 'Bearer ' . $refreshTokenModel->token,
		];
	}

	/**
	 *
	 * */
	protected static function ajaxMethods() {
		return [
			'login',
			'register',
		];
	}
}
