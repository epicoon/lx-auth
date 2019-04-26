<?php

namespace lx\auth;

use lx\Button;

class LogoutButton extends Button {
	/**
	 *
	 * */
	public static function logout() {
		$gate = \lx::$components->authenticationGate;
		$gate->logOut();
	}

	/**
	 *
	 * */
	protected static function ajaxMethods() {
		return [
			'logout',
		];
	}
}
