/**
 * @const lx.Plugin Plugin
 * */

let token = lx.Storage.get('lxauthtoken');
if (token) trySendToken(token);
else lx.createObject(Plugin.clientParams.loginForm);

function trySendToken() {
	let token = lx.Storage.get('lxauthtoken');
	let r = new lx.Request(window.location.pathname);
	r.send().then((res)=>{
		if (res.success === false) {
			if (res.message == 'expired') {
				let refreshToken = lx.Storage.get('lxauthretoken');
				if (!refreshToken) {
					lx.createObject(Plugin.clientParams.loginForm);
					return;
				}

				^Respondent.refreshTokens(refreshToken):(res)=>{
					if (!res.token || !res.refreshToken) {
						lx.createObject(Plugin.clientParams.loginForm);
						return;
					}
				
					lx.Storage.set('lxauthtoken', res.token);
					lx.Storage.set('lxauthretoken', res.refreshToken);
					trySendToken();
				};
			} else {
				lx.createObject(Plugin.clientParams.loginForm);
			}
			return;
		}

		lx.body.injectPlugin(res);
	});
}
