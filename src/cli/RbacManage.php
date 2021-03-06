<?php

namespace lx\auth\cli;

use lx\ServiceCliExecutor;

/**
 * Class RbacManage
 * @package lx\auth\cli
 */
class RbacManage extends ServiceCliExecutor
{
    public function run()
    {
        $this->sendPlugin([
            'name' => 'lx/auth:authManage',
            'header' => 'User roles manager',
            'message' => 'User roles plugin loaded',
        ]);
    }
}
