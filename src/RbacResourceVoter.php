<?php

namespace lx\auth;

use lx\AbstractResourceVoter;
use lx\User;

class RbacResourceVoter extends AbstractResourceVoter
{
	/**
	 * @param User $user
	 * @param string $actionName
	 * @param array $params
	 * @return bool
	 */
	public function run(User $user, $actionName, $params)
	{
		$authGate = $this->app->authorizationGate;
		if (!$authGate) {
		    return true;
        }

		$rights = $this->getActionRights($actionName);
		return $authGate->checkUserAccess($user, new ResourceAccessData($rights));
	}

    /**
     * @param string $actionName
     * @return array
     */
    private function getActionRights($actionName)
    {
        $map = $this->actionRightsMap();

        if (array_key_exists($actionName, $map)) {
            return $map[$actionName];
        }

        return [];
    }

    /**
     * @return array
     */
    private function actionRightsMap()
    {
        if ($this->getResource() instanceof RbacResourceInterface) {
            return $this->getResource()->getPermissions();
        }

        return [];
    }
}
