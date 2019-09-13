[English version (Английская версия)](https://github.com/epicoon/lx-auth/blob/master/README.md)

Расширение для lx-платформы, включающее аутентификацию и авторизацию.


// Пока черновик...



Подключаем компоненты к приложению (в главном конфиге):
'userModel' => 'usertest.User', - модель юзера: usertest - название сервиса, User - название самой модели
'userAuthenticateFields' => 'email', - поле (или поля), по которому (помимо пароля) будет происходить поиск юзеров
'userLoginField' => 'email', - поле (одно из userAuthenticateFields), с которым будут связаны токены

```php
'components' => [
	//...
	'authenticationGate' => [
		'class' => lx\auth\OAuth2AuthenticationGate::class,
		'userModel' => 'usertest.User',
		'userAuthenticateFields' => 'email',
		'userLoginField' => 'email',
	],

	'authorizationGate' => [
		'class' => lx\auth\RbacAuthorizationGate::class,
		'ffa' => true,

		'mock' => [
			'rightsMap' => [
				'usertest:main' => ['client_r', 'client_w']
			],
			'userRights' => [
				'default' => ['guest_r', 'guest_w'],
				'1' => ['client_r', 'client_w'],
			],
		],
	],
	//...
]
```

Внедряем сервису настройки подключения к базе (для хранения токенов)
```php
'configInjection' => [
	//...
	'lx/lx-auth' => [
		'db' => [
			'hostname' => 'localhost',
			'username' => 'lx',
			'password' => '123456',
			'dbName' => 'lxtest',
		],
	],
	//...
]
```
Проверяем, чтобы база существовала, все дела.

Накатываем миграции (через web-cli) - две миграции для создания таблиц токенов.

Проверяем для модели юзера чтобы было подключение к базе, миграции на создание таблиц для модели на мекачены.


//==============================
