<?php

namespace lx\auth\plugin\authManage;

use lx\Plugin as lxPlugin;
use lx\UserManagerInterface;

/**
 * Class Plugin
 * @package lx\auth\plugin\authManage
 */
class Plugin extends lxPlugin
{
	public function init(): void
    {
        /** @var UserManagerInterface|null $userManager */
        $userManager = $this->app->userManager;
		$this->attributes->userModel = $userManager ? $userManager->getUserModelName() : '';
	}
}
