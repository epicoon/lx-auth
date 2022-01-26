/**
 * @var lx.Plugin Plugin
 * @var lx.Snippet Snippet
 */

#lx:use lx.MultiBox;

Snippet.widget.addClass('lx-auth-back');

var body = new lx.Box({geom:true});
body.gridProportional({indent:'10px'});

var mbox = body.add(lx.MultiBox, {
	width: 12,
	marks: ['Rights and roles', 'Users']
});

var boxRights = mbox.sheet(0).add(lx.Box, {margin:'10px'});
boxRights.setPlugin({
	name: 'lx/model:relationManager',
	attributes: {
		model: 'lx/auth.Role',
		relation: 'rights'
	}
});

var boxUsers = mbox.sheet(1).add(lx.Box, {margin:'10px'});
boxUsers.setPlugin({
	name: 'lx/model:relationManager',
	attributes: {
		userModel: Plugin.attributes.userModel,
		respondentName: 'UserRole',
		getRespondentPlugin: function(core) {
			return core.plugin.parent;
		}
	}
});
