<?php

namespace lx\auth\cli;

use lx\ServiceCliExecutor;

class RbacManage extends ServiceCliExecutor
{
    public function run(): void
    {
        $this->sendPlugin([
            'name' => 'lx/auth:authManage',
            'header' => 'User roles manager',
            'message' => 'User roles plugin loaded',
        ]);
    }
}
