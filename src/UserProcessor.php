<?php

namespace lx\auth;

use lx\ApplicationToolTrait;
use lx\FusionComponentInterface;
use lx\FusionComponentTrait;
use lx\model\Model;
use lx\BaseObject;
use lx\UserEventsEnum;
use lx\UserProcessorInterface;

class UserProcessor extends BaseObject implements UserProcessorInterface, FusionComponentInterface
{
	use ApplicationToolTrait;
	use FusionComponentTrait;

	protected $serviceName = 'lx/lx-auth';
	protected $userAuthFields = 'login';
	protected $userAuthField = 'login';
	protected $userPasswordField = 'password';
	private $userModelName;

	public function __construct($config = []) {
		parent::__construct($config);

		$userModel = $config['userModel'];
		if (is_string($userModel)) {
			$this->userModelName = $userModel;
		} elseif (is_array($userModel)) {
			$this->userModelName = $userModel['service'] . '.' . $userModel['name'];
		}
	}

	public function getUserModelName()
	{
		return $this->userModelName;
	}

	public function getAuthFieldName()
	{
		return $this->userAuthField;
	}

	public function getPasswordFieldName()
	{
		return $this->userPasswordField;
	}

	public function setApplicationUser($authValue)
	{
		$userManager = $this->getUserManager();
		$userData = $userManager->loadModel([$this->userAuthField => $authValue]);
		if ( ! $userData) {
			return false;
		}

		$this->app->user->set($userData);
		$this->app->user->setAuthFieldName($this->userAuthField);
		return true;
	}

	public function getUser($condition)
	{
		$userData = $this->getUserData($condition);
		return $this->getUserByData($userData);
	}

	public function getUsers($offset = 0, $limit = null)
	{
		$dataList = $this->getUsersData($offset, $limit);
		$result = [];
		foreach ($dataList as $item) {
			$result[] = $this->getUserByData($item);
		}
		return $result;
	}

	public function findUserByPassword($login, $password) {
		$manager = $this->getUserManager();

		$fields = (array)$this->userAuthFields;
		foreach ($fields as $field) {
			$userData = $manager->loadModel([
				$field => $login,
				$this->userPasswordField => $this->getPasswordHash($password),
			]);

			if ($userData) {
				return $this->getUserByData($userData);
			}
		}

		return null;
	}

	public function createUser($authValue, $password = '')
	{
		$userManager = $this->getUserManager();

		$user = $userManager->loadModel([$this->userAuthField => $authValue]);
		if ($user) {
			return false;
		}

		$user = $userManager->newModel();
		$user->{$this->userAuthField} = $authValue;
		if ($password != '') {
			$user->{$this->userPasswordField} = $this->getPasswordHash($password);
		}
		$user->save();

		$user = $this->getUserByData($user);
		$this->app->events->trigger(UserEventsEnum::NEW_USER, $user);
		return $user;
	}

	public function deleteUser($authValue)
	{
		$user = $this->getUser($authValue);
		if ( ! $user) {
			return;
		}

		$this->app->events->trigger(UserEventsEnum::BEFORE_USER_DELETE, $user);
		$user->delete();
	}


	/*******************************************************************************************************************
	 * PROTECTED
	 ******************************************************************************************************************/

	protected function getUserManager() {
		$split = Model::splitFullName($this->userModelName);

		$service = $this->app->getService($split[0]);
		if (!$service) {
			return null;
		}

		return $service->getModelManager($split[1]);
	}

	protected function getPasswordHash($password) {
		$options = [
			'salt' => md5($password),
			'cost' => 12,
		];
		return password_hash($password, PASSWORD_DEFAULT, $options);
	}

	private function getUserByData($userData)
	{
		if ( ! $userData) {
			return null;
		}

		$class = get_class($this->app->user);
		$user = new $class();
		$user->set($userData);
		$user->setAuthFieldName($this->userAuthField);
		return $user;
	}

	private function getUserData($condition)
	{
		if (is_string($condition)) {
			$condition = [$this->userAuthField => $condition];
		}

		$manager = $this->getUserManager();
		return $manager->loadModel($condition);
	}

	private function getUsersData($offset = 0, $limit = null)
	{
		$condition = [];
		if ($offset) {
			$condition['OFFSET'] = $offset;
		}

		if ($limit) {
			$condition['LIMIT'] = $limit;
		}

		$manager = $this->getUserManager();
		return $manager->loadModels($condition);
	}
}
