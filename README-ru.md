[English version (Английская версия)](https://github.com/epicoon/lx-auth/blob/master/README.md)

Расширение для lx-платформы, включающее аутентификацию и авторизацию.


// Пока черновик...



Подключаем компоненты к приложению (в главном конфиге):
'userModel' => 'usertest.User', - модель юзера: usertest - название сервиса, User - название самой модели
'userAuthFields' => 'email', - поле (или поля), по которому (помимо пароля) будет происходить поиск юзеров
'userAuthField' => 'email', - поле (одно из userAuthFields), с которым будут связаны токены

```php
'components' => [
	//...
	'authenticationGate' => [
		'class' => lx\auth\OAuth2AuthenticationGate::class,
		'userModel' => 'usertest.User',
		'userAuthFields' => 'email',
		'userAuthField' => 'email',
	],

	'authorizationGate' => [
		'class' => lx\auth\RbacAuthorizationGate::class,
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
