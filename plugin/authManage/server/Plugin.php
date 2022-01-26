<?php

namespace lx\auth\plugin\authManage\server;

use lx;
use lx\Plugin as lxPlugin;
use lx\UserManagerInterface;

class Plugin extends lxPlugin
{
    public function init(): void
    {
        $userManager = lx::$app->userManager;
        $this->attributes->userModel = $userManager ? $userManager->getUserModelName() : '';
    }
}
