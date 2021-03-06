<?php

namespace lx\auth\cli;

use lx\CliProcessor;
use lx\FusionComponentInterface;
use lx\FusionComponentTrait;
use lx\ObjectTrait;
use lx\ServiceCliInterface;

/**
 * Class Cli
 * @package lx\auth\cli
 */
class Cli implements FusionComponentInterface, ServiceCliInterface
{
    use ObjectTrait;
    use FusionComponentTrait;

    public function getExtensionData()
    {
        return [
            [
                'type' => CliProcessor::COMMAND_TYPE_WEB_ONLY,
                'command' => 'rbac-manage',
                'description' => 'User roles management',
                'handler' => RbacManage::class,
            ],
        ];
    }
}
