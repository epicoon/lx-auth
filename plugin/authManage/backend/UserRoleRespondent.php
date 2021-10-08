<?php

namespace lx\auth\plugin\authManage\backend;

use lx;
use lx\UserManagerInterface;
use lx\auth\RbacAuthorizationGate;
use lx\auth\models\Role;
use lx\auth\models\UserRole;
use lx\model\modelTools\ModelsSerializer;
use lx\model\plugins\relationManager\backend\Respondent;
use lx\model\Model;
use lx\ResponseInterface;
use lx\FlightRecorderHolderTrait;
use lx\FlightRecorderHolderInterface;

class UserRoleRespondent extends Respondent implements FlightRecorderHolderInterface
{
    use FlightRecorderHolderTrait;
    
	public function getCoreData(array $attributes): ResponseInterface
	{
	    $userModelName = $attributes['userModel'];

	    /** @var string&Model $modelClass */
        $modelClass = Model::getModelClassName($userModelName);
        return $this->prepareResponse([
            'serviceName' => $modelClass::getModelService()->name,
            'modelName' => $modelClass::getStaticModelName(),
            'relation' => 'roles',
        ]);
	}

    public function getRelationData(
        string $serviceName,
        string $modelName,
        string $relationName,
        array $filters
    ): ResponseInterface
    {
        $modelClass = $this->defineModelClass($serviceName, $modelName);
        if ($this->hasFlightRecords()) {
            return $this->prepareWarningResponse($this->getFirstFlightRecord());
        }

        /**
         * @var Model[] $users
         * @var int $usersTotalCount
         */
        list($users, $usersTotalCount) = $this
            ->loadModels($modelClass, $filters[0] ?? []);
        /**
         * @var Model[] $roles
         * @var int $rolesTotalCount
         */
        list($roles, $rolesTotalCount) = $this
            ->loadModels(Role::class, $filters[1] ?? []);

        $serializer = new ModelsSerializer();
        $fields0 = $modelClass::getSchemaArray()['fields'];
        //TODO PK!!!
        $fields0['id'] = ['type' => 'pk'];
        $usersData = [
            'schema' => $fields0,
            'list' => $serializer->collectionToArray($users),
        ];
        $fields1 = Role::getSchemaArray()['fields'];
        //TODO PK!!!
        $fields1['id'] = ['type' => 'pk'];
        $rolesData = [
            'schema' => $fields1,
            'list' => $serializer->collectionToArray($roles),
        ];

        /** @var UserManagerInterface $userManager */
        $userManager = lx::$app->userManager;
        $authField = $userManager->getAuthFieldName();
        $usersMap = [];
        foreach ($users as $user) {
            $authValue = $user->getField($authField);
            $usersMap[$authValue] = $user;
        };

        $relationsMap = [];
        $userRoles = UserRole::find(['userAuthValue' => array_keys($usersMap)]);
        /** @var UserRole $userRole */
        foreach ($userRoles as $userRole) {
            $list = $userRole->roles;
            foreach ($list as $role) {
                $relationsMap[] = [$usersMap[$userRole->userAuthValue]->getId(), $role->getId()];
            }
        }

        return $this->prepareResponse([
            'count0' => $usersTotalCount,
            'count1' => $rolesTotalCount,
            'models0' => $usersData,
            'models1' => $rolesData,
            'relatedServiceName' => 'lx/auth',
            'relatedModelName' => 'Role',
            'relations' => $relationsMap,
        ]);
    }

    public function createRelation(
        string $serviceName,
        string $modelName,
        int $pk0,
        string $relationName,
        int $pk1
    ): ?ResponseInterface
	{
	    /** @var UserManagerInterface $userManager */
	    $userManager = lx::$app->userManager;
	    $user = $userManager->identifyUserById($pk0);
	    $role = Role::findOne($pk1);

	    /** @var RbacAuthorizationGate $gate */
	    $gate = lx::$app->authorizationGate;
	    $gate->setUserRoles($user, [$role]);

	    return null;
	}

	public function deleteRelation(
        string $serviceName,
        string $modelName,
        int $pk0,
        string $relationName,
        int $pk1
    ): ?ResponseInterface
	{
        /** @var UserManagerInterface $userManager */
        $userManager = lx::$app->userManager;
        $user = $userManager->identifyUserById($pk0);
        $role = Role::findOne($pk1);

        /** @var RbacAuthorizationGate $gate */
        $gate = lx::$app->authorizationGate;
        $gate->unsetUserRoles($user, [$role]);

        return null;
	}

    public function createModel(string $serviceName, string $modelName, array $fields): ?ResponseInterface
	{
        $modelClass = $this->defineModelClass($serviceName, $modelName);
		if ($modelClass == Role::class) {
			return parent::createModel($serviceName, $modelName, $fields);
		}

		$userManager = lx::$app->userManager;
		$authFieldName = $userManager->getAuthFieldName();
		$passFieldName = $userManager->getPasswordFieldName();
		$auth = $fields[$authFieldName] ?? null;
		$pass = $fields[$passFieldName] ?? '';

		if (!$auth) {
            return $this->prepareWarningResponse("User field $authFieldName is required");
		}

        unset($fields[$authFieldName]);
		unset($fields[$passFieldName]);

		$user = $userManager->createUser($auth, $pass, $fields);
		if (!$user) {
            return $this->prepareWarningResponse("User with this $authFieldName aready exists");
		}

		return null;
	}

    public function deleteModel(string $serviceName, string $modelName, int $pk): ?ResponseInterface
	{
        $modelClass = $this->defineModelClass($serviceName, $modelName);
        if ($modelClass == Role::class) {
            return parent::deleteModel($serviceName, $modelName, $pk);
        }

        $userManager = lx::$app->userManager;
        $user = $userManager->identifyUserById($pk);
        if (!$user) {
            return $this->prepareWarningResponse('User not found');
        }

        $userManager->deleteUser($user->getAuthValue());
        return null;
	}
}
