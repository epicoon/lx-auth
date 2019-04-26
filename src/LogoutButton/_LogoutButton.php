<?php

namespace lx\auth;

use lx\Button;

class LogoutButton extends Button {
	/**
	 *
	 * */
	public static function logout() {
		OAuth2AuthenticationGate::logOut();
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
