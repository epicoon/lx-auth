[English version (Английская версия)](https://github.com/epicoon/lx-auth/blob/master/README.md)

Расширение для lx-платформы, включающее аутентификацию и авторизацию.

Подключаем компоненты к приложению (в главном конфиге):

```php
'components' => [
	'userManager' => [
		'class' => lx\auth\UserManager::class,
		
		// модель юзера: usertest - название сервиса, User - название самой модели
		'userModel' => 'usertest.User',
		
		// поле (или поля), по которому (помимо пароля) будет происходить поиск юзеров
		'userAuthFields' => 'email',
		
		// поле (одно из userAuthFields), с которым будут связаны токены
		'userAuthField' => 'email',
	],

	'authenticationGate' => lx\auth\OAuth2AuthenticationGate::class,
	'authorizationGate' => lx\auth\RbacAuthorizationGate::class,
]
```

Проверяем, что работает CRUD

Накатываем миграции
