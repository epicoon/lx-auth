<?php

namespace lx\auth\plugin\authManage\backend;

use lx\model\ModelCollection;
use lx\model\plugin\relationManager\backend\Respondent;

class UserRoleRespondent extends Respondent
{
	private $roleModelName = 'lx/lx-auth.AuthRole';
	private $userRoleModelName = 'lx/lx-auth.AuthUserRole';

	public function getBaseInfo($userModelName, $condition0, $condition1, $pages)
	{
		$users = $this->loadModels($userModelName, $condition0, $pages[0]['page'], $pages[0]['count']);
		$roles = $this->loadModels($this->roleModelName, $condition1, $pages[1]['page'], $pages[1]['count']);

		list($service, $modelManager0) = $this->getServiceAndManager($userModelName);
		$usersCount = $modelManager0->getModelsCount();
		list($service1, $modelManager1) = $this->getServiceAndManager($this->roleModelName);
		$rolesCount = $modelManager1->getModelsCount();

		$userRoleManager = $this->app->getModelManager($this->userRoleModelName);

		$userRoleList = new ModelCollection();
		$authFieldName = $this->app->userProcessor->getAuthFieldName();
		foreach ($users as $user) {
			$userRoleList[] = $userRoleManager->loadModel([
				'user_auth_data' => $user->{$authFieldName},
			]);
		}

		$usersMap = $users->mapByField($authFieldName);
		$userRolesMap = $userRoleList->mapByField('id');

		if ($roles->isEmpty() || $userRoleList->isEmpty()) {
			$relationsMap = [];
		} else {
			$relManager = $service1->modelProvider->getRelationManager($this->userRoleModelName, $this->roleModelName);
			$relationsMap = $relManager->getRelationsPkMap($userRoleList, $roles);
			foreach ($relationsMap as &$pare) {
				$userRole = $userRolesMap[$pare[0]];
				$user = $usersMap[$userRole->user_auth_data];
				$pare[0] = $user->id;
			}
			unset($pare);
		}

		$users->setModelName($userModelName);
		$roles->setModelName($this->roleModelName);
		return [
			'users' => $users->toDeepArrayWithSchema(),
			'roles' => $roles->toDeepArrayWithSchema(),
			'relations' => $relationsMap,
			'usersCount' => $usersCount,
			'rolesCount' => $rolesCount,
		];
	}

	public function createRelation($modelName0, $pk0, $modelName1, $pk1)
	{
		list(
			$userModelName,
			$userPk,
			$roleModelName,
			$rolePk
		) = $this->translateParams($modelName0, $pk0, $modelName1, $pk1);

		list($service0, $userManager) = $this->getServiceAndManager($userModelName);
		list($service1, $roleManager) = $this->getServiceAndManager($this->roleModelName);
		$userRoleManager = $this->app->getModelManager($this->userRoleModelName);

		$user = $userManager->loadModel($userPk);
		$role = $roleManager->loadModel($rolePk);

		$authFieldName = $this->app->userProcessor->getAuthFieldName();
		$userRole = $userRoleManager->loadModel([
			'user_auth_data' => $user->{$authFieldName},
		]);

		$userRole->roles->add($role);
	}

	public function deleteRelation($modelName0, $pk0, $modelName1, $pk1)
	{
		list(
			$userModelName,
			$userPk,
			$roleModelName,
			$rolePk
		) = $this->translateParams($modelName0, $pk0, $modelName1, $pk1);

		list($service0, $userManager) = $this->getServiceAndManager($userModelName);
		list($service1, $roleManager) = $this->getServiceAndManager($this->roleModelName);
		$userRoleManager = $this->app->getModelManager($this->userRoleModelName);

		$user = $userManager->loadModel($userPk);
		$role = $roleManager->loadModel($rolePk);

		$authFieldName = $this->app->userProcessor->getAuthFieldName();
		$userRole = $userRoleManager->loadModel([
			'user_auth_data' => $user->{$authFieldName},
		]);

		$userRole->roles->remove($role);
	}

	public function createModel($modelName, $fields)
	{
		if ($modelName == $this->roleModelName) {
			parent::createModel($modelName, $fields);
			return;
		}

		$processor = $this->app->userProcessor;
		$authFieldName = $processor->getAuthFieldName();
		$passFieldName = $processor->getPasswordFieldName();
		$auth = $fields[$authFieldName] ?? null;
		$pass = $fields[$passFieldName] ?? '';
		unset($fields[$passFieldName]);

		if ( ! $auth) {
			return false;
		}

		$user = $processor->createUser($auth, $pass);
		if ( ! $user) {
			return false;
		}

		$user->setFields($fields);
		$user->save();
	}

	public function deleteModel($modelName, $pk)
	{
		if ($modelName == $this->roleModelName) {
			parent::deleteModel($modelName, $fields);
			return;
		}

		list($service0, $userManager) = $this->getServiceAndManager($modelName);
		$user = $userManager->loadModel($pk);

		$processor = $this->app->userProcessor;
		$authFieldName = $processor->getAuthFieldName();
		$auth = $user->{$authFieldName};

		$processor->deleteUser($auth);
	}

	private function translateParams($modelName0, $pk0, $modelName1, $pk1)
	{
		if ($modelName0 == $this->roleModelName) {
			return [
				$modelName1, $pk1, $modelName0, $pk0
			];
		}

		return [
			$modelName0, $pk0, $modelName1, $pk1
		];
	}
}
