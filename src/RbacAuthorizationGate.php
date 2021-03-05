<?php

namespace lx\auth;

use lx\ApplicationToolTrait;
use lx\ArrayHelper;
use lx\auth\models\DefaultList;
use lx\auth\models\Right;
use lx\auth\models\Role;
use lx\auth\models\UserRole;
use lx\AuthorizationInterface;
use lx\EventListenerInterface;
use lx\EventListenerTrait;
use lx\FusionComponentInterface;
use lx\FusionComponentTrait;
use lx\ObjectTrait;
use lx\ResourceContext;
use lx\UserEventsEnum;
use lx\UserInterface;

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
		$resourceRigths = $accessData->getData();
		foreach ($resourceRigths as $right) {
			if (!in_array($right, $userRights)) {
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

    /**
     * @param UserInterface $user
     * @param Role[] $roles
     */
	public function setUserRoles(UserInterface $user, iterable $roles)
    {
        $userRole = UserRole::findOne(['userAuthValue' => $user->getAuthValue()]);
        if (!$userRole) {
            $userRole = new UserRole([
                'userAuthValue' => $user->getAuthValue(),
            ]);
        }

        foreach ($roles as $role) {
            $userRole->roles[] = $role;
        }

        $userRole->save();
    }

    /**
     * @param UserInterface $user
     * @param Role[] $roles
     */
    public function unsetUserRoles(UserInterface $user, iterable $roles)
    {
        $userRole = UserRole::findOne(['userAuthValue' => $user->getAuthValue()]);
        if (!$userRole) {
            return;
        }

        foreach ($roles as $role) {
            $userRole->removeRelated('roles', $role);
        }

        $userRole->save();
    }
	
	/**
	 * @param UserInterface $user
	 * @return array
	 */
	private function getUserRights($user): array
	{
		$roles = $this->getUserRoles($user);
		if (ArrayHelper::isEmpty($roles)) {
			return [];
		}

		$rights = [];
		foreach ($roles as $role) {
		    $rights = ArrayHelper::merge($rights, $role->rights);
        }

		$result = [];
		/** @var Right $right */
        foreach ($rights as $right) {
		    $result[] = $right->name;
        }

		return $result;
	}

    /**
     * @param UserInterface $user
     * @return iterable|Role[]
     */
	private function getUserRoles(UserInterface $user): iterable
	{
		if ($user->isGuest()) {
			return [];
		}

		$userRole = UserRole::findOne(['userAuthValue' => $user->getAuthValue()]);
		if (!$userRole) {
		    return [];
        }

		return $userRole->roles;
	}

	private function getNewUserRoles()
	{
	    /** @var DefaultList[] $defaults */
	    $defaults = DefaultList::find([
	        //TODO ~const?
            'type' => 'new-user-role'
        ]);
        $roleNames = [];
        foreach ($defaults as $default) {
            $roleNames[] = $default->value;
        }
        $roles = Role::find([
            'name' => $roleNames,
        ]);
        return $roles;
	}


	/*******************************************************************************************************************
	 * EVENT HANDLERS
	 ******************************************************************************************************************/

    /**
     * @param UserInterface $user
     */
	private function onNewUser($user)
	{
	    $this->setUserRoles($user, $this->getNewUserRoles());
	}

	private function onUserDelete($user)
	{
        $userRole = UserRole::findOne(['userAuthValue' => $user->getAuthValue()]);
        if (!$userRole) {
            return;
        }
        
        $userRole->clearRelated('roles');
        $userRole->save();
        $userRole->delete();
	}
}
