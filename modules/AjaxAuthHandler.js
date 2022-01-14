#lx:module lx.auth.AjaxAuthHandler;

lx.subscribe(lx.EVENT_BEFORE_AJAX_REQUEST, function (request) {
    let token = lx.Storage.get('lxauthtoken');
    if (!token) return;
    request.setRequestHeader('Authorization', token);
});

lx.subscribe(lx.EVENT_AJAX_REQUEST_UNAUTHORIZED, function (response, request, options) {
    // Ignore self ajax-requests
    if (options.headers['lx-module'] == 'lx.auth.TokenUpdater:tryAuthenticate'
        || options.headers['lx-module'] == 'lx.auth.TokenUpdater:refreshTokens'
    ) return;

    lx.dependencies.promiseModules(['lx.auth.TokenUpdater', 'lx.auth.LoginForm'], ()=>{
        (new lx.auth.TokenUpdater()).run()
            .onAccepted(function () {
                lx.Dialog.request(options);
            })
            .onRejected(error=>{
                switch (error.error_code) {
                    case 401:
                        const form = new lx.auth.LoginForm({
                            registerButton: false,
                            closeButton: true
                        });
                        form.on('authenticate', ()=>{
                            form.del();
                            lx.Dialog.request(options)
                        });
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
    });
});
