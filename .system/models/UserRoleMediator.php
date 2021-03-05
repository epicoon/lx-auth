<?php

namespace lx\auth\sys\models;

use lx\modelnew\Model;
use lx\modelnew\modelTools\RelatedModelsCollection;
use lx\auth\models\Role;

/**
 * Class UserRoleMediator
 * @package lx\auth\sys\models
 *
 * @property string $userAuthValue
 * @property RelatedModelsCollection&Role[] $roles
 */
class UserRoleMediator extends Model
{
    public static function getServiceName(): string
    {
        return 'lx/auth';
    }

    public static function getSchemaArray(): array
    {
        return [
            'name' => 'UserRole',
            'fields' => [
                'userAuthValue' => [
                    'type' => 'string',
                    'required' => false,
                ],
            ],
            'relations' => [
                'roles' => [
                    'type' => 'manyToMany',
                    'relatedEntityName' => 'Role',
                    'relatedAttributeName' => 'userRoles',
                ],
            ],
        ];
    }
}
