<?php

namespace lx\auth;

use lx;

class Service extends \lx\Service
{
    public function getJsModules(): array
    {
        $gate = lx::$app->authenticationGate;
        if (!$gate) {
            return [];
        }

        return $gate->getJsModules();
    }
}
