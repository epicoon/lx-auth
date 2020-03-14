<?php

namespace lx\auth;

use lx\AbstractSourceVoter;
use lx\User;

class SourceVoter extends AbstractSourceVoter
{
	/**
	 * @return array
	 */
	protected function actionRightsMap()
	{
		if (method_exists($this->getSource(), 'actionRightsMap')) {
			return $this->getSource()->actionRightsMap();
		}
		
		return [];
	}

	/**
	 * @param User $user
	 * @param string $actionName
	 * @param array $params
	 * @return bool
	 */
	public function run(User $user, $actionName, $params)
	{
		$authGate = $this->app->authorizationGate;

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
}
