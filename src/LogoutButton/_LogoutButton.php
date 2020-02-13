<?php

namespace lx\auth;

use lx\Rect;

class LogoutButton extends Rect {
	public function logout() {
		$gate = $this->app->authenticationGate;
		$gate->logOut();
	}
}
