<?php

namespace lx\auth;

use lx\Plugin;
use lx\Service;
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
use lx\ResourceAccessDataInterface;
use lx\ResourceContext;
use lx\UserEventsEnum;
use lx\UserInterface;

class RbacAuthorizationGate implements AuthorizationInterface, FusionComponentInterface
{
    use ObjectTrait;
	use ApplicationToolTrait;
	use FusionComponentTrait;
	use EventListenerTrait;

	public static function getEventHandlersMap(): array
	{
		return [
			UserEventsEnum::NEW_USER => 'onNewUser',
			UserEventsEnum::BEFORE_USER_DELETE => 'onUserDelete',
		];
	}

	public function checkUserAccess(UserInterface $user, ResourceAccessDataInterface $resourceAccessData): bool
	{
		$userRights = $this->getUserRights($user);
		$resourceRigths = $resourceAccessData->getData();
		foreach ($resourceRigths as $right) {
			if (!in_array($right, $userRights)) {
				return false;
			}
		}
		
		return true;
	}

    /**
     * @param iterable<Role> $roles
     */
	public function setUserRoles(UserInterface $user, iterable $roles): void
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
     * @param iterable<Role> $roles
     */
    public function unsetUserRoles(UserInterface $user, iterable $roles): void
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
	
	private function getUserRights(UserInterface $user): array
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
     * @return iterable<Role>
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

    /**
     * @return iterable<Role>
     */
	private function getNewUserRoles(): iterable
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


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * EVENT HANDLERS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function onNewUser(UserInterface $user): void
	{
	    $this->setUserRoles($user, $this->getNewUserRoles());
	}

	private function onUserDelete(UserInterface $user): void
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
