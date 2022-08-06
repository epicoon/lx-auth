class Plugin extends lx.Plugin {
    initCss(css) {
        css.addClass('lx-auth-back', {
            backgroundColor: this.cssPreset.altMainBackgroundColor,
        });
    }

    run() {

    }
}
