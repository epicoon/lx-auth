<?php

namespace lx\auth;

use lx\ApplicationComponent;
use lx\AuthorizationInterface;
use lx\ResponseSource;

/**
 *
 * */
class RbacAuthorizationGate extends ApplicationComponent implements AuthorizationInterface {
	/** @var boolean Free for all - доступен ли ресурс гостю, если права на него явно не указаны */
	protected $ffa = true;
	protected $authModuleName = 'lx/lx-auth:authManage';

	//TODO времянка
	protected $mock;

	/**
	 *
	 * */
	public function __construct($config = []) {
		parent::__construct($config);
	}

	/**
	 *
	 * */
	public function checkAccess($user, $responseSource) {
		$rights = $this->getRightsForSource($responseSource);
		// var_dump($rights);
		
		if ($rights === false) {
			$responseSource->addRestriction(ResponseSource::RESTRICTION_FORBIDDEN_FOR_ALL);
			return $responseSource;
		} elseif ($rights === true) {
			return $responseSource;
		}

		$userRights = $this->getUserRights($user);
		// var_dump($userRights);
		foreach ($rights as $right) {
			if (!in_array($right, $userRights)) {
				$responseSource->addRestriction(ResponseSource::RESTRICTION_INSUFFICIENT_RIGHTS);
				break;
			}
		}

		return $responseSource;
	}

	/**
	 *
	 * */
	public function getManageModule() {
		return \lx::getModule($this->authModuleName);
	}

	/**
	 *
	 * */
	private function getRightsForSource($responseSource) {
		$map = $this->getRightsMap();
		$key = $responseSource->getSourceName();
		if (array_key_exists($key, $map)) {
			return $map[$key];
		} else {
			return $this->ffa;
		}
	}

	/**
	 *
	 * */
	private function getRightsMap() {

		//TODO
		return $this->mock['rightsMap'];
	}

	/**
	 *
	 * */
	private function getUserRights($user) {
		// $role = $this->getUserRole($user);
		// ...


		//TODO
		$map = $this->mock['userRights'];
		if (array_key_exists((string)$user->id, $map)) {
			return $map[$user->id];
		}

		return $map['default'];
	}

	/**
	 *
	 * */
	private function getUserRole($user) {

		//TODO
		return 'client';
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
