<?php

namespace lx\auth\plugin\authManage;

class Plugin extends \lx\Plugin {
	public function beforeCompile() {
		$this->clientParams->userModel = $this->app->userProcessor->getUserModelName();
	}
}
