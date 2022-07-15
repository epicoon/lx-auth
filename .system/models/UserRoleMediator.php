<?php

namespace lx\auth\sys\models;

use lx\model\Model;
use lx\model\modelTools\RelatedModelsCollection;
use lx\auth\models\Role;

/**
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
