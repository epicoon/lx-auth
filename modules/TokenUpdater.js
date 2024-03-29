#lx:module lx.auth.TokenUpdater;
#lx:module-data {
    backend: lx\auth\modules\TokenUpdater
};

#lx:namespace lx.auth;
class TokenUpdater extends lx.Module {
    constructor() {
        super();
        this._onAccepted = function(){};
        this._onRejected = function(){};
    }

    onAccepted(callback) {
        this._onAccepted = callback;
        return this;
    }

    onRejected(callback) {
        this._onRejected = callback;
        return this;
    }

    run() {
        let token = lx.app.storage.get('lxauthtoken');
        if (!token) {
            setTimeout(()=>this.__runRejected({error_code: 401}), 100);
            return this;
        }

        this.__trySendToken();
        return this;
    }

    refresh() {
        if (!this.__tryRefreshTokens())
            this.__runRejected({
                error_code: 401,
                error_details: 'expired'
            });
        return this;
    }

    reject(error) {
        this.__runRejected(error);
    }
    
    __trySendToken() {
        ^self::tryAuthenticate()
            .then(res=>{
                lx.app.user.set(res.data);
                this.__runAccepted()
            })
            .catch(res=>{
                if (res.error_code == 401 && res.error_details == 'expired' && this.__tryRefreshTokens()) return;
                this.__runRejected(res);
            });
    }

    __tryRefreshTokens() {
        let refreshToken = lx.app.storage.get('lxauthretoken');
        if (!refreshToken) return false;

        ^self::refreshTokens(refreshToken)
            .then(res=>{
                if (!res.data.accessToken || !res.data.refreshToken || !res.data.userData) {
                    this.__runRejected(res);
                    return;
                }
                lx.app.storage.set('lxauthtoken', res.data.accessToken);
                lx.app.storage.set('lxauthretoken', res.data.refreshToken);
                lx.app.user.set(res.data.userData);
                this.__runAccepted();
            })
            .catch(res=>{
                this.__runRejected(res);
            });
        return true;
    }

    __runAccepted() {
        try {
            this._onAccepted.call(this);
        } catch (e) {
            this._onRejected.call(this, e);
        }
    }
    
    __runRejected(error) {
        this._onRejected.call(this, error);
    }
}
