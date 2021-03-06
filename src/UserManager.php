<?php

namespace lx\auth;

use lx\ApplicationToolTrait;
use lx\AuthenticationInterface;
use lx\FusionComponentInterface;
use lx\FusionComponentTrait;
use lx\ObjectTrait;
use lx\UserEventsEnum;
use lx\UserInterface;
use lx\UserManagerInterface;
use lx\ModelInterface;
//TODO желательно отвязаться от модели в пользу интерфейса lx\ModelInterface
use lx\model\Model;

class UserManager implements UserManagerInterface, FusionComponentInterface
{
    use ObjectTrait;
	use ApplicationToolTrait;
	use FusionComponentTrait;

    private $userAuthFields = ['login'];
    private $userAuthField = 'login';
    private $userPasswordField = 'password';
	private $publicFields = [];
	private $userModelName;

	public function __construct(array $config = []) {
	    $this->__objectConstruct($config);

		$userModel = $config['userModel'];
		if (is_string($userModel)) {
			$this->userModelName = $userModel;
		} elseif (is_array($userModel)) {
			$this->userModelName = $userModel['service'] . '.' . $userModel['name'];
		}
	}

	public function getUserModelName(): string
	{
		return $this->userModelName;
	}

	public function getAuthFieldName(): string
	{
		return $this->userAuthField;
	}

	public function getPasswordFieldName(): string
	{
		return $this->userPasswordField;
	}

    public function identifyUserById(int $id, ?UserInterface $defaultUser = null): ?UserInterface
    {
        $userClass = $this->getUserModelClass();
        $userModel = $userClass::findOne($id);
        if ($userModel) {
            return $this->wrapUpUserModel($userModel, $defaultUser);
        }

        return null;
    }

    /**
     * @param mixed $userAuthValue
     * @param UserInterface|null $defaultUser
     * @return UserInterface|null
     */
    public function identifyUserByAuthValue($userAuthValue, ?UserInterface $defaultUser = null): ?UserInterface
    {
        $userClass = $this->getUserModelClass();
        $fields = (array)$this->userAuthFields;
        foreach ($fields as $field) {
            $userModel = $userClass::findOne([$field => $userAuthValue]);
            if ($userModel) {
                return $this->wrapUpUserModel($userModel, $defaultUser);
            }
        }

        return null;
    }

    /**
     * @param mixed $userAuthValue
     * @param string $password
     * @param UserInterface|null $defaultUser
     * @return UserInterface|null
     */
    public function identifyUserByPassword(
        $userAuthValue,
        string $password,
        ?UserInterface $defaultUser = null
    ): ?UserInterface
    {
        $userClass = $this->getUserModelClass();
        $fields = (array)$this->userAuthFields;
        foreach ($fields as $field) {
            $userModel = $userClass::findOne([
                $field => $userAuthValue,
                $this->userPasswordField => $this->getPasswordHash($password),
            ]);
            if ($userModel) {
                return $this->wrapUpUserModel($userModel, $defaultUser);
            }
        }

        return null;
    }

	public function getPublicData(?UserInterface $user = null): array
    {
        if ($user === null) {
            $user = $this->app->user;
        }
        
        if ($user->isGuest()) {
            return [];
        }

        $model = $user->getModel();
        $result = [];
        foreach ($this->publicFields as $fieldName) {
            if ($model->hasField($fieldName)) {
                $result[$fieldName] = $model->getField($fieldName);
                
            }
        }

        return $result;
    }

    /**
     * @param int|null $offset
     * @param int|null $limit
     * @return UserInterface[]
     */
	public function getUsers(?int $offset = 0, ?int $limit = null): array
	{
        $userClass = $this->getUserModelClass();
        $models = $userClass::find([
            'OFFSET' => $offset,
            'LIMIT' => $limit,
        ]);
        $result = [];
        foreach ($models as $model) {
            $result[] = $this->wrapUpUserModel($model);
        }
        
		return $result;
	}
	
    /**
     * @param mixed $authValue
     * @param string $password
     * @param array $fields
     * @return UserInterface|null
     */
	public function createUser($userAuthValue, string $password, array $fields = []): ?UserInterface
	{
	    $userClass = $this->getUserModelClass();
        $currentUserModel = $userClass::findOne([$this->userAuthField => $userAuthValue]);
        if ($currentUserModel) {
            return null;
        }

        $userModel = new $userClass(array_merge([
            $this->userAuthField => $userAuthValue,
            $this->userPasswordField => $this->getPasswordHash($password),
        ], $fields));
        $userModel->save();

        $user = $this->wrapUpUserModel($userModel);
        $this->app->events->trigger(UserEventsEnum::NEW_USER, $user);
        return $user;
	}

    /**
     * @param mixed $authValue
     */
	public function deleteUser($userAuthValue)
	{
		$user = $this->identifyUserByAuthValue($userAuthValue);
		if (!$user) {
			return;
		}

		$this->app->events->trigger(UserEventsEnum::BEFORE_USER_DELETE, $user);
		$user->getModel()->delete();
	}
	
	protected function getPasswordHash($password) {
		$options = [
			'salt' => md5($password),
			'cost' => 12,
		];
		return password_hash($password, PASSWORD_DEFAULT, $options);
	}
	
	private function wrapUpUserModel(ModelInterface $userModel, ?UserInterface $user = null): UserInterface
    {
        if ($user === null) {
            $class = get_class($this->app->user);
            /** @var UserInterface $user */
            $user = $this->app->diProcessor->create($class);
        }
        
        $user->setModel($userModel);
        $user->setAuthFieldName($this->userAuthField);
        return $user;
    }

    /**
     * @return string&Model
     */
    private function getUserModelClass(): string
    {
        return Model::getModelClassName($this->userModelName);
    }
}
