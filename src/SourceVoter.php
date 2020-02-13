<?php

namespace lx\auth;

use lx\ApplicationToolTrait;
use lx\Object;
use lx\Source;
use lx\SourceVoterInterface;
use lx\User;

class SourceVoter extends Object implements SourceVoterInterface
{
	use ApplicationToolTrait;

	protected $owner;

	protected function actionRightsMap()
	{
		return [];
	}

	public function setSource(Source $source)
	{
		$this->owner = $source;
	}

	public function run(User $user, $actionName, $params)
	{
		$authGate = $this->app->authorizationGate;

		$rights = $this->getActionRights($actionName);
		return $authGate->checkUserHasRights($user, $rights);
	}

	public function processActionParams(User $user, $actionName, $params)
	{
		return $params;
	}

	private function getActionRights($actionName)
	{
		$map = $this->actionRightsMap();
		if (array_key_exists($actionName, $map)) {
			return $map[$actionName];
		}

		return [];
	}
}
