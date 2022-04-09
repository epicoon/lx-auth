#lx:module lx.auth.LogoutButton;
#lx:module-data {
	backend: lx\auth\LogoutButton
};

#lx:use lx.Button;

#lx:namespace lx.auth;
class LogoutButton extends lx.Button {
	#lx:client clientBuild(config) {
		super.clientBuild(config);

		this.click(()=>{
			^self::logout().then((res)=>location.reload());
		});
	}
}
