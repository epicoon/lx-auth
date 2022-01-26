class Plugin extends lx.Plugin {
    initCssAsset(css) {
        css.addClass('lx-auth-back', {
            backgroundColor: this.cssPreset.altMainBackgroundColor,
        });
    }

    run() {

    }
}
