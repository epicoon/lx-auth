<?php

namespace lx\auth;

use lx\Rect;

class LogoutButton extends Rect {
	protected static function ajaxMethods() {
		return [
			'logout',
		];
	}

	public function logout() {
		$gate = $this->app->authenticationGate;
		$gate->logOut();
	}
}
