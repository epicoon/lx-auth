<?php

namespace lx\auth\plugin\authManage\server;

use lx;
use lx\Plugin as lxPlugin;
use lx\UserManagerInterface;

class Plugin extends lxPlugin
{
    protected function init(): void
    {
        parent::init();
        
        $userManager = lx::$app->userManager;
        $this->attributes->userModel = $userManager ? $userManager->getUserModelName() : '';
    }
}
