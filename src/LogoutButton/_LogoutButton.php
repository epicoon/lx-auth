<?php

namespace lx\auth;

use lx;
use lx\Module;
use lx\HttpResponseInterface;

class LogoutButton extends Module
{
	public function logout(): void
    {
		$gate = lx::$app->authenticationGate;
        if ($gate) {
            $gate->logOut();
        }
	}
}
