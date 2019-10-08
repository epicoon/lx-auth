<?php

namespace lx\auth;

use lx\ApplicationComponent;
use lx\AuthorizationInterface;
use lx\ResponseSource;

class RbacAuthorizationGate extends ApplicationComponent implements AuthorizationInterface
{
	protected $rbacServiceName = 'lx/lx-auth';
	protected $rbacManagePluginName = 'lx/lx-auth:authManage';

	//TODO времянка
	protected $mock;

	public function __construct($config = [])
	{
		parent::__construct($config);
	}

	public function checkAccess($user, $responseSource)
	{
		$rights = $this->getRightsForSource($responseSource);
		$userRights = $this->getUserRights($user);
		foreach ($rights as $right) {
			if (!in_array($right, $userRights)) {
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
		$map = $this->getResourceRightsMap();
		$key = $responseSource->getSourceName();
		return array_key_exists($key, $map)
			? $map[$key]
			: $this->getDefaultResourceRights();
	}






	private function getResourceRightsMap()
	{

		//TODO
		return $this->mock['rightsMap'];
	}

	private function getDefaultResourceRights()
	{

		//TODO
		return $this->mock['defaultResourceRights'];
	}

	private function getUserRights($user)
	{



		//TODO
		$map = $this->mock['userRights'];
		if (array_key_exists((string)$user->id, $map)) {
			return $map[$user->id];
		}

		return $this->mock['defaultUserRights'];
	}










	/*
	у пользователя есть роль
	за ролью закреплен список доступных прав
	роль может включать в себя другие роли, н-р [[admin]] включает права [[user]]

	роль влияет на доступ к роутам (при использовании опции [[for]])

	право влияет на доступ к роутам (при использовании опции [[right]])
	право влияет на исполнение метода респондента, настраивается в респонденте
	право влияет на исполнение экшена (класса), настраивается в экшене
	право влияет на исполнение экшена контроллера, настраивается в контроллере

	??????
	! как добавить права специфичные для конкретного сервиса
	имя права будет выглядеть как %имя/сервиса%.%имя_права%


	Нужны модули для управления:
	- списком прав
	- деревом ролей
	- назначением ролей юзерам
	- мониторинг внутренних прав подключаемых сервисов
	*/
}
