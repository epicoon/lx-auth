/**
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

#lx:use lx.auth.TokenUpdater;

(new lx.auth.TokenUpdater()).run()
	.onAccepted(function (userData) {
		(new lx.Request(document.location.pathname)).send()
			.then(res => {
				if (!res) {
					this.reject({error_code: 500});
					return;
				}

				lx.User.set(userData);
				if (lx.isString(res)) lx.body.html(res);
				else if (lx.isObject(res) && res.data && res.data.pluginInfo)
					lx.body.setPlugin(res.data);
				else lx.body.html(JSON.stringify(res));
			})
			.catch(res => this.reject(res));
	})
	.onRejected(error=>{
		switch (error.error_code) {
			case 401:
				lx.createObject(Plugin.attributes.loginForm);
				break;
			case 403:
				lx.Tost.warning('Resource is unavailable');
				break;
			case 404:
				lx.Tost.warning('User not found');
				break;
			default: lx.Tost.error(error.error_details || 'Internal server error');
		}
	});
