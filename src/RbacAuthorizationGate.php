<?php

namespace lx\auth;

use lx\ApplicationComponent;
use lx\AuthorizationInterface;
use lx\ResponseSource;
use lx\User;

class RbacAuthorizationGate extends ApplicationComponent implements AuthorizationInterface
{
	protected $rbacServiceName = 'lx/lx-auth';
	protected $rbacManagePluginName = 'lx/lx-auth:authManage';

	public function __construct($config = [])
	{
		parent::__construct($config);
	}

	public function checkAccess($user, $responseSource)
	{
		$rights = $this->getRightsForSource($responseSource);
		$userRights = $this->getUserRights($user);
		foreach ($rights as $right) {
			if ( ! in_array($right, $userRights)) {
				$responseSource->addRestriction(ResponseSource::RESTRICTION_INSUFFICIENT_RIGHTS);
				break;
			}
		}

		return $responseSource;
	}

	public function getService()
	{
		return $this->app->getService($this->rbacServiceName);
	}

	public function getManagePlugin()
	{
		return $this->app->getPlugin($this->rbacManagePluginName);
	}

	protected function getModelManager($modelName) {
		$service = $this->getService();
		if (!$service) {
			return null;
		}

		return $service->getModelManager($modelName);
	}

	/**
	 * @param $responseSource ResponseSource
	 * @return array
	 */
	private function getRightsForSource($responseSource)
	{
		$sourceRightManager = $this->getModelManager('AuthSourceRight');
		$sourceRightModel = $sourceRightManager->loadModel([
			'source_name' => $responseSource->getSourceName()
		]);

		if ( ! $sourceRightModel) {
			return $this->getDefaultResourceRights();
		}

		return $sourceRightModel->rights->getField('name');
	}

	private function getDefaultResourceRights()
	{
		$defaultListManager = $this->getModelManager('AuthDefaultList');
		$defaultRightsData = $defaultListManager->loadModels(['type' => 'right']);
		if ($defaultRightsData->isEmpty()) {
			return [];
		}

		$rightManager = $this->getModelManager('AuthRight');
		return $rightManager->loadModels($defaultRightsData->getField('id_item'))->getField('name');
	}

	/**
	 * @param $user User
	 * @return mixed
	 */
	private function getUserRights($user)
	{
		$roles = $this->getUserRoles($user);
		$rights = $roles->getField('rights');
		$result = [];
		foreach ($rights as $rightList) {
			foreach ($rightList as $right) {
				$rightName = $right->name;
				if (array_search($rightName, $result) === false) {
					$result[] = $rightName;
				}
			}
		}

		return $result;
	}

	private function getUserRoles($user)
	{
		$defaultRoles = $this->getDefaultRoles();
		if ($user->isGuest()) {
			return $defaultRoles;
		}

		$userRoleManager = $this->getModelManager('AuthUserRole');
		$userRoleModel = $userRoleManager->loadModel([
			'user_auth_data' => $user->{$user->getAuthFieldName()}
		]);
		if ( ! $userRoleModel) {
			return $defaultRoles;
		}

		return $defaultRoles->merge($userRoleModel->roles);
	}

	private function getDefaultRoles()
	{
		$defaultListManager = $this->getModelManager('AuthDefaultList');
		$models = $defaultListManager->loadModels(['type' => 'role']);
		if ( ! $models) {
			return [];
		}

		$roleManager = $this->getModelManager('AuthRole');
		$roles = $roleManager->loadModels($models->getField('id_item'));
		return $roles;
	}
}
