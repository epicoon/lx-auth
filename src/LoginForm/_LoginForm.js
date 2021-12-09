#lx:module lx.auth.LoginForm;
#lx:module-data {
	i18n: i18n.yaml,
	backend: lx\auth\LoginForm
};

#lx:use lx.Box;
#lx:use lx.Input;
#lx:use lx.Button;

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

	#lx:client clientBuild(config) {
		super.clientBuild(config);

		this->send.click(()=>{
			^self::login(this->login.value(), this->password.value()).then(res=>{
				if (res.success === false) {
					lx.Tost.warning(res.error_details[0]);
					return;
				}

				__applyTokens(res.data.token, res.data.refreshToken);
			}).catch(res=>{
				switch (res.error_code) {
					case 404:
						lx.Tost.warning('User not found');
						break;
				}
			});
		});

		this->register.click(()=>{
			^self::register(this->login.value(), this->password.value()).then(res=>{
				if (res.success === false) {
					lx.Tost.warning(res.error_details[0]);
					return;
				}

				__applyTokens(res.data.token, res.data.refreshToken);
			});
		});
	}
}

#lx:client {
	function __applyTokens(token, refreshToken) {
		lx.Storage.set('lxauthtoken', token);
		lx.Storage.set('lxauthretoken', refreshToken);

		window.location.reload();
		//TODO может так оно получше, только надо понимать что дальше с полученным делать. Коллбэк?
		// var r = new lx.HttpRequest(window.location.pathname);
		// r.send().then((res)=>lx.body.setPlugin(res));
	}
}
