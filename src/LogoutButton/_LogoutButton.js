class LogoutButton extends lx.Button #lx:namespace lx.auth {
	/**
	 *
	 * */
	postBuild(config) {
		super.postBuild(config);

		this.addClass('lx-Button');
		this.click(()=>{
			^self::logout():(res)=>{location.reload()};
		});
	}
}
