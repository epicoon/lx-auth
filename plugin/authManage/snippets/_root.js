/**
 * @var lx.Plugin Plugin
 * @var lx.Snippet Snippet
 * */

/*
* Управление правами (создание, удаление)
	просто таблица, куда можно добавить право, или удалить существующее
* Управление ролями (создание, удаление, связывание с правами)
	таблица, куда можно добавить роль, или удалить существующую
	плюс таблица прав, чтобы можно было связывать
+ Альтернатива - CollectBox, в котором будет сразу всё - слева права, справа роли, по середине пересечения
	там сразу можно будет и права и роли создавать/удалять, и связи между ними создавать/удалять

* Управление правами на ресурсы
	- Идеал
		определяется множество ресурсов, их типы, их названия
		строится таблица ресурсов
		выводится таблица ролей
		делается виджет (видимо надо сделать новый - CollectBox)
			при выборе ресурса - в этом виджете отобрадаются нужные на него права
			есть возможность выбрать право и удалить его (удаляется связь)
			как-то организована связь с таблицей прав (или даже это часть виджета), откуда можно добавить права
	- Кратчайший вариант
		в предыдущем варианте таблицу ресурсов меняем на инпут и вводим имя ресурса вручную

* Управление ролями пользователей
	! в кишках цепочка такая User -> AuthUserRole -> AuthRole
		работу с посредником от интерфейса нужно скрыть
	визуально хорошо бы оформить подобно CollectBox из рассуждений выше
*/

#lx:use lx.MultiBox;

Snippet.widget.fill('white');

var body = new lx.Box({geom:true});
body.grid({indent:'10px'});
body.begin();
	var mbox = new lx.MultiBox({
		width: 12,
		marks: ['Rights and roles', 'Resources', 'Users']
	});

	var boxRights = mbox.sheet(0).add(lx.Box, {geom:['10px', '10px', null, null, '10px', '10px']});
	boxRights.setPlugin({
		name: 'lx/lx-model:relationManager',
		clientParams: {
			models: ['lx/lx-auth.AuthRight', 'lx/lx-auth.AuthRole']
		}
	});

	var boxResources = mbox.sheet(1).add(lx.Box, {geom:['10px', '10px', null, null, '10px', '10px']});



	var boxUsers = mbox.sheet(2).add(lx.Box, {geom:['10px', '10px', null, null, '10px', '10px']});
	boxUsers.setPlugin({
		name: 'lx/lx-model:relationManager',
		clientParams: {
			models: ['lx/lx-auth.AuthRight', 'lx/lx-auth.AuthRole']
		}
	});

body.end();




// boxUsers.setPlugin({
// 	name: 'lx/lx-model:relationManager',
// 	clientParams: {
// 		models: ['lx/lx-auth.AuthRight', 'lx/lx-auth.AuthRole']
// 		// ,
// 		// eventHandlers: userHandlers
// 	}
// });

// var userHandlers = {
// 	start: '()=>console.log(123);'
// };
