#lx:module lx.auth.LoginForm;
#lx:module-data {
	i18n: i18n.yaml,
	backend: lx\auth\LoginForm
};

#lx:use lx.Box;
#lx:use lx.Input;
#lx:use lx.Button;

/* 
 * Special events:
 * - authenticate()
 */
#lx:namespace lx.auth;
class LoginForm extends lx.Box {
	modifyConfigBeforeApply(config) {
		config.geom = true;
		return config;
	}

	getBasicCss() {
		return {
			form: 'lx-auth-LoginForm',
			background: 'lx-auth-LoginForm-back'
		};
	}

	static initCssAsset(css) {
		css.inheritClass('lx-auth-LoginForm', 'AbstractBox');
		css.addClass('lx-auth-LoginForm-back', {
			opacity: 0.5,
			backgroundColor: css.preset.widgetIconColor
		});
	}

	/* config = {
	 *	// стандартные для Box,
	 *
	 * {Boolean} registerButton
	 * {Boolean} closeButton
	 * }
	 */
	build(config) {
		super.build(config);

		this.add(lx.Rect, {geom: true, css: this.basicCss.background});
		const form = this.add(lx.Box, {key: 'form', geom: [30, 20, 40, 40], css: this.basicCss.form});
		form.grid({indent: '10px'});

		var box = form.add(lx.Box, {text: #lx:i18n(lx.auth.LoginForm.Login), width:12});
		box.align(lx.CENTER, lx.MIDDLE);
		form.add(lx.Input, {key: 'login', width:12});

		var box = form.add(lx.Box, {text: #lx:i18n(lx.auth.LoginForm.Password), width:12});
		box.align(lx.CENTER, lx.MIDDLE);
		form.add(lx.Input, {key: 'password', width:12});
		form->password.setAttribute('type', 'password');

		let count = 1,
			registerButton = lx.getFirstDefined(config.registerButton, true),
			closeButton = lx.getFirstDefined(config.closeButton, false);
		if (registerButton) count++;
		if (closeButton) count++;
		let width = 12 / count;
		form.add(lx.Button, {width, key: 'send', text: #lx:i18n(lx.auth.LoginForm.Send)});
		if (registerButton)
			form.add(lx.Button, {width, key: 'register', text: #lx:i18n(lx.auth.LoginForm.Register)});
		if (closeButton)
			form.add(lx.Button, {width, key: 'close', text: #lx:i18n(lx.auth.LoginForm.Close)});
	}

	#lx:client clientBuild(config) {
		super.clientBuild(config);

		const form = this->form;

		form->send.click(()=>{
			^self::login(form->login.value(), form->password.value())
				.then(res=>__applyTokens(this, res.data.token, res.data.refreshToken))
				.catch(res=>lx.Tost.error(res.error_details));
		});

		if (form.contains('register')) {
			form->register.click(()=>{
				^self::register(form->login.value(), form->password.value())
					.then(res=>__applyTokens(this, res.data.token, res.data.refreshToken))
					.catch(res=>lx.Tost.error(res.error_details));
			});
		}

		if (form.contains('close')) {
			form->close.click(()=>this.del());
		}
	}
}

#lx:client {
	function __applyTokens(self, token, refreshToken) {
		lx.Storage.set('lxauthtoken', token);
		lx.Storage.set('lxauthretoken', refreshToken);
		
		self.trigger('authenticate');
	}
}
