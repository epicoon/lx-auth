<?php

namespace lx\auth\plugin\authManage;

class Plugin extends \lx\Plugin {
	public function init() {
		$this->attributes->userModel = $this->app->userProcessor
			? $this->app->userProcessor->getUserModelName()
			: '';
	}
}
