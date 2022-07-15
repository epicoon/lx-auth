<?php

namespace lx\auth\plugin\getToken\server;

use lx;

class Plugin extends \lx\Plugin
{
    protected function init(): void
    {
        parent::init();
        
        $gate = lx::$app->authenticationGate;
        if ($gate) {
            $loginForm = $gate->getLoginFormName();
            $this->addAttribute('loginForm', $loginForm);
            $this->addDependencies(['modules' => [$loginForm]]);
        }
    }
}
