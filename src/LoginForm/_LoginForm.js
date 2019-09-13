#lx:module lx.auth.LoginForm;
#lx:module-data {
	i18n: i18n.yaml
};

#lx:use lx.Box;
#lx:use lx.Input;
#lx:use lx.Button;

#lx:private;

/**
 * Форма логина
 * */
class LoginForm extends lx.Box #lx:namespace lx.auth {
	modifyConfigBeforeApply(config) {
		if (!config.geom) {
			config.geom = [30, 20, 40, 40];
		}

		return config;
	}

	build(config) {
		super.build(config);
		
		this.fill('lightgray');
		this.grid({indent: '10px'});

		var box = this.add(lx.Box, {text: #lx:i18n(lx.auth.LoginForm.Login), width:12});
		box.align(lx.CENTER, lx.MIDDLE);
		this.add(lx.Input, {key: 'login', width:12});

		var box = this.add(lx.Box, {text: #lx:i18n(lx.auth.LoginForm.Password), width:12});
		box.align(lx.CENTER, lx.MIDDLE);
		this.add(lx.Input, {key: 'password', width:12});
		this->password.setAttribute('type', 'password');

		this.add(lx.Button, {width: 6, key: 'send', text: #lx:i18n(lx.auth.LoginForm.Send)});
		this.add(lx.Button, {width: 6, key: 'register', text: #lx:i18n(lx.auth.LoginForm.Register)});
	}

	#lx:client postBuild(config) {
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

#lx:client {
	function saveTokens(token, refreshToken) {
		lx.Storage.set('lxauthtoken', token);
		lx.Storage.set('lxauthretoken', refreshToken);

		var r = new lx.Request(window.location.pathname);
		r.send().then((res)=>lx.body.injectPlugin(res));
	}
}
