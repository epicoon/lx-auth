#lx:use lx.auth.TokenUpdater;

class Plugin extends lx.Plugin {
	run() {
		(new lx.auth.TokenUpdater()).run()
			.onAccepted(function() {
				(new lx.HttpRequest(document.location.pathname)).send()
					.then(res => {
						if (!res) {
							this.reject({error_code: 500});
							return;
						}

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
						const form = lx.createObject(this.attributes.loginForm);
						form.on('authenticate', ()=>window.location.reload());
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
	}
}
