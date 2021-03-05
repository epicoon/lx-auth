<?php

namespace lx\auth\sys\models;

use lx\modelnew\Model;
use lx\modelnew\modelTools\RelatedModelsCollection;
use lx\auth\models\Role;

/**
 * Class RightMediator
 * @package lx\auth\sys\models
 *
 * @property string $name
 * @property RelatedModelsCollection&Role[] $roles
 */
class RightMediator extends Model
{
    public static function getServiceName(): string
    {
        return 'lx/auth';
    }

    public static function getSchemaArray(): array
    {
        return [
            'name' => 'Right',
            'fields' => [
                'name' => [
                    'type' => 'string',
                    'required' => false,
                ],
            ],
            'relations' => [
                'roles' => [
                    'type' => 'manyToMany',
                    'relatedEntityName' => 'Role',
                    'relatedAttributeName' => 'rights',
                ],
            ],
        ];
    }
}
