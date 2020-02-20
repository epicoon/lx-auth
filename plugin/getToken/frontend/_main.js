/**
 * @const lx.Plugin Plugin
 * */

let token = lx.Storage.get('lxauthtoken');
if (token) trySendToken();
else lx.createObject(Plugin.clientParams.loginForm);

function trySendToken() {
	let token = lx.Storage.get('lxauthtoken');
	^Respondent.tryAuthenticate().then((res)=>tryAuth(res));
}

function tryAuth(res) {
	if (res.success === true) {
		(new lx.Request(document.location.pathname)).send().then((res)=>{
			if (res.success === false) {
				//TODO пока не знаю что и как сюда удобно будет впилить
				console.log(res);
			} else {
				lx.body.injectPlugin(res)
			}
		});
	} else if (res.success === false) {
		if (res.message == 'expired') {
			let refreshToken = lx.Storage.get('lxauthretoken');
			if (!refreshToken) {
				lx.createObject(Plugin.clientParams.loginForm);
				return;
			}

			^Respondent.refreshTokens(refreshToken).then((res)=>{
				if (!res.token || !res.refreshToken) {
					lx.createObject(Plugin.clientParams.loginForm);
					return;
				}

				lx.Storage.set('lxauthtoken', res.token);
				lx.Storage.set('lxauthretoken', res.refreshToken);
				trySendToken();
			});
		} else {
			lx.createObject(Plugin.clientParams.loginForm);
		}
		return;
	}
}
