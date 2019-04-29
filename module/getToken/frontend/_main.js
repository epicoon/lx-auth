/**
 * @const lx.Module Module
 * */

let token = lx.Storage.get('lxauthtoken');
if (token) trySendToken(token);
else lx.createObject(Module.params.loginForm);

function trySendToken() {
	let token = lx.Storage.get('lxauthtoken');
	let r = new lx.Request(window.location.pathname);
	r.send().then((res)=>{
		if (res.success === false) {
			if (res.message == 'expired') {
				let refreshToken = lx.Storage.get('lxauthretoken');
				if (!refreshToken) {
					lx.createObject(Module.params.loginForm);
					return;
				}

				^Respondent.refreshTokens(refreshToken):(res)=>{
					if (!res.token || !res.refreshToken) {
						lx.createObject(Module.params.loginForm);
						return;
					}
				
					lx.Storage.set('lxauthtoken', res.token);
					lx.Storage.set('lxauthretoken', res.refreshToken);
					trySendToken();
				};
			} else {
				lx.createObject(Module.params.loginForm);
			}
			return;
		}

		lx.body.injectModule(res);
	});
}
