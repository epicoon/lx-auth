#lx:module lx.auth.LogoutButton;
#lx:module-data {
	backend: lx\auth\LogoutButton
};

#lx:use lx.Button;

#lx:namespace lx.auth;
class LogoutButton extends lx.Button {
	#lx:client clientRender(config) {
		super.clientRender(config);

		this.click(()=>{
			^self::logout().then((res)=>location.reload());
		});
	}
}
