<?php

namespace lx\auth\sys\models;

use lx\model\Model;
use lx\model\modelTools\RelatedModelsCollection;
use lx\auth\models\Right;
use lx\auth\models\UserRole;

/**
 * @property string $name
 * @property RelatedModelsCollection&Right[] $rights
 * @property RelatedModelsCollection&UserRole[] $userRoles
 */
class RoleMediator extends Model
{
    public static function getServiceName(): string
    {
        return 'lx/auth';
    }

    public static function getSchemaArray(): array
    {
        return [
            'name' => 'Role',
            'fields' => [
                'name' => [
                    'type' => 'string',
                    'required' => false,
                ],
            ],
            'relations' => [
                'rights' => [
                    'type' => 'manyToMany',
                    'relatedEntityName' => 'Right',
                    'relatedAttributeName' => 'roles',
                ],
                'userRoles' => [
                    'type' => 'manyToMany',
                    'relatedEntityName' => 'UserRole',
                    'relatedAttributeName' => 'roles',
                ],
            ],
        ];
    }
}
