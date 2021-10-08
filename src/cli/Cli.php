<?php

namespace lx\auth\cli;

use lx\CliProcessor;
use lx\FusionComponentInterface;
use lx\FusionComponentTrait;
use lx\ObjectTrait;
use lx\ServiceCliInterface;

class Cli implements FusionComponentInterface, ServiceCliInterface
{
    use FusionComponentTrait;

    public function getCliCommandsConfig(): array
    {
        return [
            [
                'type' => CliProcessor::COMMAND_TYPE_WEB,
                'command' => 'rbac-manage',
                'description' => 'User roles management',
                'handler' => RbacManage::class,
            ],
        ];
    }
}
