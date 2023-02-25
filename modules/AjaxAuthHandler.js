#lx:module lx.auth.AjaxAuthHandler;

lx.app.lifeCycle.subscribe(lx.EVENT_BEFORE_AJAX_REQUEST, function (request) {
    let token = lx.app.storage.get('lxauthtoken');
    if (!token) return;
    request.setRequestHeader('Authorization', token);
});

lx.app.lifeCycle.subscribe(lx.EVENT_AJAX_REQUEST_UNAUTHORIZED, function (response, request, options) {
    // Ignore self ajax-requests
    if (options.headers['lx-module'] == 'lx.auth.TokenUpdater:tryAuthenticate'
        || options.headers['lx-module'] == 'lx.auth.TokenUpdater:refreshTokens'
    ) return;

    lx.app.loader.loadModules({
        modules: ['lx.auth.TokenUpdater', 'lx.auth.LoginForm'],
        callback: ()=>{
            (new lx.auth.TokenUpdater()).refresh()
                .onAccepted(()=>lx.app.dialog.request(options))
                .onRejected(error=>{
                    switch (error.error_code) {
                        case 401:
                            const form = new lx.auth.LoginForm({
                                registerButton: false,
                                closeButton: true
                            });
                            form.on('authenticate', ()=>{
                                form.del();
                                lx.app.dialog.request(options)
                            });
                            break;
                        case 403:
                            lx.tostWarning('Resource is unavailable');
                            break;
                        case 404:
                            lx.tostWarning('User not found');
                            break;
                        default: lx.tostError(error.error_details || 'Internal server error');
                    }
                });
        }
    });
});
