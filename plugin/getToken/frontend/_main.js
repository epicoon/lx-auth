/**
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

let token = lx.Storage.get('lxauthtoken');
if (token) trySendToken();
else lx.createObject(Plugin.attributes.loginForm);

function trySendToken() {
	^Respondent.tryAuthenticate()
		.then(res=>tryAuth(res.data))
		.catch(res=>checkResultProblems(res));
}

function tryAuth(res) {
	lx.User.set(res);
	(new lx.Request(document.location.pathname)).send()
		.then(res=>{
			if (!res) return;

			if (res.isString) {
				lx.body.html(res);
			} else if (res.isObject && res.data && res.data.pluginInfo) {
				lx.body.setPlugin(res.data);
			} else {
				lx.body.html(JSON.stringify(res));
			}
		})
		.catch(res=>checkResultProblems(res));
}

function tryRefreshTokens() {
	let refreshToken = lx.Storage.get('lxauthretoken');
	if (!refreshToken) return false;

	^Respondent.refreshTokens(refreshToken)
		.then(res=>refreshTokens(res.data))
		.catch(res=>checkResultProblems(res));

	return true;
}

function refreshTokens(res) {
	if (res.token && res.refreshToken) {
		lx.Storage.set('lxauthtoken', res.token);
		lx.Storage.set('lxauthretoken', res.refreshToken);
		trySendToken();
	}
}

function checkResultProblems(res) {
	switch (res.error_code) {
		case 401:
			if (res.error_details && res.error_details[0] == 'expired' && tryRefreshTokens()) break;
			lx.createObject(Plugin.attributes.loginForm);
			break;
		case 403:
			lx.Tost.warning('Resource is unavailable');
			break;
		case 404:
			lx.Tost.warning('User not found');
			break;
		default: lx.Tost.error(res.error_details || 'Internal server error');
	}
}
