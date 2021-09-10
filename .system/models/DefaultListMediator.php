<?php

namespace lx\auth\sys\models;

use lx\model\Model;

/**
 * Class DefaultListMediator
 * @package lx\auth\sys\models
 *
 * @property string $type
 * @property string $value
 */
class DefaultListMediator extends Model
{
    public static function getServiceName(): string
    {
        return 'lx/auth';
    }

    public static function getSchemaArray(): array
    {
        return [
            'name' => 'DefaultList',
            'fields' => [
                'type' => [
                    'type' => 'string',
                    'required' => false,
                ],
                'value' => [
                    'type' => 'string',
                    'required' => false,
                ],
            ],
            'relations' => [],
        ];
    }
}
