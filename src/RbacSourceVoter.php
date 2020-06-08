<?php

namespace lx\auth;

use lx\AbstractSourceVoter;
use lx\User;

class RbacSourceVoter extends AbstractSourceVoter
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
		return $authGate->checkUserAccess($user, new SourceAccessData($rights));
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
        if (method_exists($this->getSource(), 'getPermissions')) {
            return $this->getSource()->getPermissions();
        }

        return [];
    }
}
