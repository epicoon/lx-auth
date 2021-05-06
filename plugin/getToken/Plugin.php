<?php

namespace lx\auth\plugin\getToken;

use lx\auth\OAuth2AuthenticationGate;

class Plugin extends \lx\Plugin
{
	protected function init(): void
	{
		/** @var OAuth2AuthenticationGate $gate */
		$gate = $this->app->authenticationGate;
		if ($gate) {
			$loginForm = $gate->getLoginFormName();
			$this->addAttribute('loginForm', $loginForm);
			$this->addDependencies(['modules' => [$loginForm]]);
		}
	}
}
