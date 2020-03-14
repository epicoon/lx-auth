<?php

namespace lx\auth\plugin\authManage;

class Plugin extends \lx\Plugin {
	public function init() {
		$this->params->userModel = $this->app->userProcessor
			? $this->app->userProcessor->getUserModelName()
			: '';
	}
}
