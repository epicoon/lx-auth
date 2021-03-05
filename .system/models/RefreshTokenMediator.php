<?php

namespace lx\auth\sys\models;

use lx\modelnew\Model;

/**
 * Class RefreshTokenMediator
 * @package lx\auth\sys\models
 *
 * @property string $token
 * @property string $userAuthValue
 * @property string $expire
 */
class RefreshTokenMediator extends Model
{
    public static function getServiceName(): string
    {
        return 'lx/auth';
    }

    public static function getSchemaArray(): array
    {
        return [
            'name' => 'RefreshToken',
            'fields' => [
                'token' => [
                    'type' => 'string',
                    'required' => false,
                ],
                'userAuthValue' => [
                    'type' => 'string',
                    'required' => false,
                ],
                'expire' => [
                    'type' => 'string',
                    'required' => false,
                ],
            ],
            'relations' => [],
        ];
    }
}
