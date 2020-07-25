/**
 * @var lx.Plugin Plugin
 * @var lx.Snippet Snippet
 * */

/**
 * const userRoleEventHandlers Object;
 */
#lx:require eventHandlers/;

#lx:use lx.MultiBox;

Snippet.widget.fill('white');

var body = new lx.Box({geom:true});
body.grid({indent:'10px'});
body.begin();
	var mbox = new lx.MultiBox({
		width: 12,
		marks: ['Rights and roles', 'Users']
	});

	var boxRights = mbox.sheet(0).add(lx.Box, {geom:['10px', '10px', null, null, '10px', '10px']});
	boxRights.setPlugin({
		name: 'lx/model:relationManager',
		attributes: {
			models: ['lx/auth.AuthRight', 'lx/auth.AuthRole']
		}
	});

	var boxUsers = mbox.sheet(1).add(lx.Box, {geom:['10px', '10px', null, null, '10px', '10px']});
	boxUsers.setPlugin({
		name: 'lx/model:relationManager',
		attributes: {
			models: [Plugin.attributes.userModel, 'lx/auth.AuthRole'],
			eventHandlers: userRoleEventHandlers
		}
	});
body.end();
