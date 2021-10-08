<?php

namespace lx\auth;

use lx;
use lx\AbstractResourceVoter;
use lx\UserInterface;

class RbacResourceVoter extends AbstractResourceVoter
{
	public function run(UserInterface $user, string $actionName, array $params): bool
	{
		$authGate = lx::$app->authorizationGate;
		if (!$authGate) {
		    return true;
        }

		$rights = $this->getActionRights($actionName);
		if (empty($rights)) {
		    return true;
        }

		return $authGate->checkUserAccess($user, new ResourceAccessData($rights));
	}

    private function getActionRights(string $actionName): array
    {
        $map = $this->actionRightsMap();

        if (array_key_exists($actionName, $map)) {
            return $map[$actionName];
        }

        return [];
    }

    private function actionRightsMap(): array
    {
        if ($this->getResource() instanceof RbacResourceInterface) {
            return $this->getResource()->getPermissions();
        }

        return [];
    }
}
