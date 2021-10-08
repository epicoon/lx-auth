<?php

namespace lx\auth;

use lx;
use lx\AuthenticationInterface;
use lx\FusionComponentInterface;
use lx\FusionComponentTrait;
use lx\ObjectTrait;
use lx\UserEventsEnum;
use lx\UserInterface;
use lx\UserManagerInterface;
use lx\ModelInterface;

class UserManager implements UserManagerInterface, FusionComponentInterface
{
	use FusionComponentTrait;

    private array $userAuthFields = ['login'];
    private string $userAuthField = 'login';
    private string $userPasswordField = 'password';
	private array $publicFields = [];
	private string $userModelClass;

	public function __construct(array $config = [])
    {
	    $this->__objectConstruct($config);
	    $this->userModelClass = $config['userModel'];
    }

	public function getUserModelName(): string
	{
		return $this->userModelClass::getModelService()->name . '.' . $this->userModelClass::getStaticModelName();
	}

	public function getAuthFieldName(): string
	{
		return $this->userAuthField;
	}

	public function getPasswordFieldName(): string
	{
		return $this->userPasswordField;
	}

    /**
     * @return mixed
     */
	public function getAuthField(ModelInterface $userModel)
    {
        return $userModel->getField($this->getAuthFieldName());
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
            $user = lx::$app->user;
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
     * @return array<UserInterface>
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
    
    public function getUser(ModelInterface $userData): UserInterface
    {
        return $this->wrapUpUserModel($userData);
    }
	
    /**
     * @param mixed $authValue
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
        lx::$app->events->trigger(UserEventsEnum::AFTER_USER_CREATED, $user);
        return $user;
	}

    /**
     * @param mixed $authValue
     */
	public function deleteUser($userAuthValue): void
	{
		$user = $this->identifyUserByAuthValue($userAuthValue);
		if (!$user) {
			return;
		}

		lx::$app->events->trigger(UserEventsEnum::BEFORE_USER_DELETE, $user);
		$user->getModel()->delete();
	}
	
	protected function getPasswordHash(string $password): string
    {
		$options = [
			'salt' => md5($password),
			'cost' => 12,
		];
		return password_hash($password, PASSWORD_DEFAULT, $options);
	}
	
	private function wrapUpUserModel(ModelInterface $userModel, ?UserInterface $user = null): UserInterface
    {
        if ($user === null) {
            $class = get_class(lx::$app->user);
            /** @var UserInterface $user */
            $user = lx::$app->diProcessor->create($class);
        }
        
        $user->setModel($userModel);
        $user->setAuthFieldName($this->userAuthField);
        return $user;
    }

    /**
     * @return string
     */
    private function getUserModelClass(): string
    {
        return $this->userModelClass;
    }
}
