/**
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

let token = lx.Storage.get('lxauthtoken');
if (token) trySendToken();
else lx.createObject(Plugin.params.loginForm);

function trySendToken() {
	^Respondent.tryAuthenticate().then((res)=>tryAuth(res));
}

function tryAuth(res) {
	if (res.success === true) {
		(new lx.Request(document.location.pathname)).send().then((res)=>{
			if (res.success === false) {
				//TODO пока не знаю что и как сюда удобно будет впилить
				console.log(res);
			} else {
				lx.body.setPlugin(res)
			}
		});
	} else if (res.success === false) {
		if (res.message == 'expired') {
			let refreshToken = lx.Storage.get('lxauthretoken');
			if (!refreshToken) {
				lx.createObject(Plugin.params.loginForm);
				return;
			}

			^Respondent.refreshTokens(refreshToken).then((res)=>{
				if (!res.token || !res.refreshToken) {
					var el = lx.createObject(Plugin.params.loginForm);
					return;
				}

				lx.Storage.set('lxauthtoken', res.token);
				lx.Storage.set('lxauthretoken', res.refreshToken);
				trySendToken();
			});
		} else {
			lx.createObject(Plugin.params.loginForm);
		}
		return;
	}
}
