#lx:module lx.auth.LogoutButton;

#lx:use lx.Button;

class LogoutButton extends lx.Button #lx:namespace lx.auth {
	#lx:client postBuild(config) {
		super.postBuild(config);

		this.click(()=>{
			^self::logout():(res)=>{location.reload()};
		});
	}
}
