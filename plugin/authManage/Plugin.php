<?php

namespace lx\auth\plugin\authManage;

use lx\Plugin as lxPlugin;

/**
 * Class Plugin
 * @package lx\auth\plugin\authManage
 */
class Plugin extends lxPlugin
{
    /**
     * @return void
     */
	public function init()
    {
		$this->attributes->userModel = $this->app->userProcessor
			? $this->app->userProcessor->getUserModelName()
			: '';
	}
}
