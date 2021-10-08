#lx:module lx.auth.TokenUpdater;
#lx:module-data {
    backend: lx\auth\modules\TokenUpdater
};

class TokenUpdater extends lx.Module #lx:namespace lx.auth {
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
        let token = lx.Storage.get('lxauthtoken');
        if (!token) {
            setTimeout(()=>this._onRejected(), 100);
            return this;
        }

        this.__trySendToken();
        return this;
    }
    
    reject(error) {
        this.__runRejected(error);
    }
    
    __trySendToken() {
        ^self::tryAuthenticate()
            .then(res=>{
                this.__runAccepted(res.data)
            })
            .catch(res=>{
                if (res.error_code == 401 && res.error_details == 'expired' && this.__tryRefreshTokens()) return;
                this.__runRejected(res);
            });
    }

    __tryRefreshTokens() {
        let refreshToken = lx.Storage.get('lxauthretoken');
        if (!refreshToken) return false;

        ^self::refreshTokens(refreshToken)
            .then(res=>{
                if (!res.data.accessToken || !res.data.refreshToken || !res.data.userData) {
                    this.__runRejected(res);
                    return;
                }
                lx.Storage.set('lxauthtoken', res.data.accessToken);
                lx.Storage.set('lxauthretoken', res.data.refreshToken);
                this.__runAccepted(res.data.userData);
            })
            .catch(res=>{
                this.__runRejected(res);
            });
        return true;
    }

    __runAccepted(userData) {
        try {
            this._onAccepted.call(this, userData);
        } catch (e) {
            this._onRejected.call(this, e);
        }
    }
    
    __runRejected(error) {
        this._onRejected.call(this, error);
    }
}