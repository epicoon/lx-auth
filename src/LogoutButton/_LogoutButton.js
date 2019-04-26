class LogoutButton extends lx.Button #lx:namespace lx.auth {
	/**
	 *
	 * */
	postBuild(config) {
		super.postBuild(config);

		this.addClass('lx-Button');
		let url = this.url || 'logout';
		this.click(()=>{
			(new lx.Request(url)).send().then(()=>location.reload());
		});
	}
}
