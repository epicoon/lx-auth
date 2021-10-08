#lx:module lx.auth.TokenReader;

lx.subscribe(lx.EVENT_BEFORE_AJAX_REQUEST, function (request) {
    let token = lx.Storage.get('lxauthtoken');
    if (!token) return;
    request.setRequestHeader('Authorization', token);
});
