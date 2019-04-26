/**
 * @const lx.Module Module
 * */

#lx:widget lx.auth.LoginForm;

let token = lx.Storage.get('lxauthtoken');
if (token) trySendToken(token);
else new lx.auth.LoginForm();

function trySendToken() {
	let token = lx.Storage.get('lxauthtoken');
	let r = new lx.Request(window.location.pathname);
	r.send().then((res)=>{

		console.log(res);

		if (res.success === false) {
			if (res.message == 'expired') {
				let refreshToken = lx.Storage.get('lxauthretoken');
				if (!refreshToken) {
					new lx.auth.LoginForm();
					return;
				}

				^Respondent.refreshTokens(refreshToken):(res)=>{
					if (!res.token || !res.refreshToken) {
						new lx.auth.LoginForm();
						return;
					}
				
					lx.Storage.set('lxauthtoken', res.token);
					lx.Storage.set('lxauthretoken', res.refreshToken);
					trySendToken();
				};
			} else {
				new lx.auth.LoginForm();
			}
			return;
		}

		lx.body.injectModule(res);
	});
}
