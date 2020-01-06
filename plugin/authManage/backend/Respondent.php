<?php

namespace lx\auth\plugin\authManage\backend;

class Respondent extends \lx\Respondent
{
	public function eee()
	{
		$manager = $this->app->authenticationGate->getUserManager();


		
	}



	public function loadUsers()
	{
		$manager = $this->app->authenticationGate->getUserManager();
		$list = $manager->loadModels();

		$array = $list->toDeepArray(null, ['password']);
		return $array;
	}

	public function loadUserRoles()
	{
		$manager = $this->service->getModelManager('AuthUserRole');
		$list = $manager->loadModels();

		$array = $list->toDeepArray();
		return $array;
	}



	public function loadRoles()
	{
		$manager = $this->service->getModelManager('AuthRole');
		$roles = $manager->loadModels();
		$roles = $roles->toDeepArray();

		$manager = $this->service->getModelManager('AuthRight');
		$rights = $manager->loadModels();
		$rights = $rights->toDeepArray();

		return [
			'roles' => $roles,
			'rights' => $rights,
		];
	}

}
