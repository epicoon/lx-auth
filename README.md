[Russian version (Русская версия)](https://github.com/epicoon/lx-auth/blob/master/README-ru.md)

Authentification and authorization extension for lx-platform.

Set components in main application configuration:

```php
'components' => [
	'userProcessor' => [
		'class' => lx\auth\UserProcessor::class,
		
		// user model: usertest - service name, User - model name
		'userModel' => 'usertest.User',
		
		// field (or fields) for user search
		'userAuthFields' => 'email',
		
		// field (one from userAuthFields) for token relation
		'userAuthField' => 'email',
	],

	'authenticationGate' => lx\auth\OAuth2AuthenticationGate::class,
	'authorizationGate' => lx\auth\RbacAuthorizationGate::class,
]
```

Check CRUD is working

Make migrations
