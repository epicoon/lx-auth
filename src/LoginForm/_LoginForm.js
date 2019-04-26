#lx:private;

/**
 * Форма логина
 * */
class LoginForm extends lx.Box #lx:namespace lx.auth {
	/**
	 *
	 * */
	preBuild(config) {
		if (!config.geom) {
			config.geom = [30, 20, 40, 40];
		}

		return config;
	}

	/**
	 *
	 * */
	build(config) {
		super.build(config);
		
		this.fill('lightgray');
		this.grid({indent: '10px'});

		var box = this.add(lx.Box, {text: #lx:i18n(lx.auth.LoginForm.Login)});
		box.align(lx.CENTER, lx.MIDDLE);
		this.add(lx.Input, {key: 'login'});

		var box = this.add(lx.Box, {text: #lx:i18n(lx.auth.LoginForm.Password)});
		box.align(lx.CENTER, lx.MIDDLE);
		this.add(lx.Input, {key: 'password'});
		this->password.attr('type', 'password');

		this.add(lx.Button, {width: 6, key: 'send', text: #lx:i18n(lx.auth.LoginForm.Send)});
		this.add(lx.Button, {width: 6, key: 'register', text: #lx:i18n(lx.auth.LoginForm.Register)});
	}

	/**
	 *
	 * */
	postBuild(config) {
		super.postBuild(config);

		this->send.click(()=>{
			^self::login(this->login.value(), this->password.value()):(res)=>{
				if (res.result === false) {
					lx.Tost.warning(res.message);
					return;
				}

				saveTokens(res.token, res.refreshToken);
			};
		});

		this->register.click(()=>{
			^self::register(this->login.value(), this->password.value()):(res)=>{
				if (res.result === false) {
					lx.Tost.warning(res.message);
					return;
				}

				saveTokens(res.token, res.refreshToken);
			};
		});
	}
}

/**
 *
 * */
function saveTokens(token, refreshToken) {
	lx.Storage.set('lxauthtoken', token);
	lx.Storage.set('lxauthretoken', refreshToken);

	var r = new lx.Request(window.location.pathname);
	r.send().then((res)=>lx.body.injectModule(res));
}
