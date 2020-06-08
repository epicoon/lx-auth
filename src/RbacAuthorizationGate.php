<?php

namespace lx\auth;

use lx\ApplicationToolTrait;
use lx\AuthorizationInterface;
use lx\EventListenerInterface;
use lx\EventListenerTrait;
use lx\FusionComponentInterface;
use lx\FusionComponentTrait;
use lx\ObjectTrait;
use lx\SourceContext;
use lx\User;
use lx\UserEventsEnum;

class RbacAuthorizationGate implements AuthorizationInterface, FusionComponentInterface
{
    use ObjectTrait;
	use ApplicationToolTrait;
	use FusionComponentTrait;
	use EventListenerTrait;

	protected $rbacServiceName = 'lx/auth';
	protected $rbacManagePluginName = 'lx/auth:authManage';

	public static function getEventHandlersMap()
	{
		return [
			UserEventsEnum::NEW_USER => 'onNewUser',
			UserEventsEnum::BEFORE_USER_DELETE => 'onUserDelete',
		];
	}

	public function checkUserAccess($user, $accessData)
	{
		$userRights = $this->getUserRights($user);
		$sourceRigths = $accessData->getData();
		foreach ($sourceRigths as $right) {
			if ( ! in_array($right, $userRights)) {
				return false;
			}
		}

		return true;
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
	 * @param User $user
	 * @return mixed
	 */
	private function getUserRights($user)
	{
		$result = [];
		$roles = $this->getUserRoles($user);
		if ( ! $roles) {
			return $result;
		}

		$rights = $roles->getField('rights');
		foreach ($rights as $rightList) {
			foreach ($rightList->get() as $right) {
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
		if ($user->isGuest()) {
			return null;
		}

		$userRoleManager = $this->getModelManager('AuthUserRole');
		$userRoleModel = $userRoleManager->loadModel([
			'user_auth_data' => $user->getAuthField()
		]);
		if ( ! $userRoleModel) {
			return null;
		}

		return $userRoleModel->roles->get();
	}

	private function getNewUserRoles()
	{
		$defaultListManager = $this->getModelManager('AuthDefaultList');
		$models = $defaultListManager->loadModels(['type' => 'new-user-role']);
		if ($models->isEmpty()) {
			return $models;
		}

		$roleManager = $this->getModelManager('AuthRole');
		$roles = $roleManager->loadModels($models->getField('id_item'));
		return $roles;
	}


	/*******************************************************************************************************************
	 * EVENT HANDLERS
	 ******************************************************************************************************************/

	private function onNewUser($user)
	{
		$userRoleManager = $this->getModelManager('AuthUserRole');
		$userRole = $userRoleManager->newModel();
		$userRole->user_auth_data = $user->getAuthField();
		$userRole->save();

		$roles = $this->getNewUserRoles();
		$userRole->roles->add($roles);
	}

	private function onUserDelete($user)
	{
		$userRoleManager = $this->getModelManager('AuthUserRole');
		$userRole = $userRoleManager->loadModel([
			'user_auth_data' => $user->getAuthField()
		]);
		if ($userRole) {
			$userRole->removeAllRelations();
			$userRole->delete();
		}
	}
}
