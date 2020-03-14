const userRoleEventHandlers = {
	start: (core, condition0 = '', condition1 = '', page0 = 0, page1 = 0)=>{
		var plugin = core.plugin.parent;
		plugin.ajax('UserRole.getBaseInfo', [
			plugin.params.userModel,
			condition0,
			condition1,
			[
				{page: page0, count: core.modelData0.perPage},
				{page: page1, count: core.modelData1.perPage}
			]
		]).send().then((res)=>{
			core.modelData0.list = lx.ModelCollection.create(res.users);
			core.modelData1.list = lx.ModelCollection.create(res.roles);
			core.relations = res.relations;

			core.plugin.root.getSide(0)->>pager.elementsPerPage = core.modelData0.perPage;
			core.plugin.root.getSide(1)->>pager.elementsPerPage = core.modelData1.perPage;
			core.plugin.root.getSide(0)->>pager.setElementsCount(res.usersCount);
			core.plugin.root.getSide(1)->>pager.setElementsCount(res.rolesCount);

			core.plugin.eventManager.trigger('fillBody');
		});
	},

	createRelation: function(core, modelName0, pk0, modelName1, pk1) {
		var plugin = core.plugin.parent;
		plugin.ajax('UserRole.createRelation', [
			modelName0, pk0, modelName1, pk1
		]).send().then((res)=>{
			core.onCreateRelation(modelName0, pk0, modelName1, pk1);
		});
	},

	deleteRelation: function(core, modelName0, pk0, modelName1, pk1) {
		var plugin = core.plugin.parent;
		plugin.ajax('UserRole.deleteRelation', [
			modelName0, pk0, modelName1, pk1
		]).send().then((res)=>{
			core.onCreateRelation(modelName0, pk0, modelName1, pk1);
		});
	},

	createModel: function(core, modelData, fields, callback) {
		var plugin = core.plugin.parent;
		plugin.ajax('UserRole.createModel', [modelData.fullname, fields]).send().then(callback);
	},

	deleteModel: function(core, modelData, index, callback) {
		var plugin = core.plugin.parent;
		var pk = modelData.list.at(index).getPk();
		plugin.ajax('UserRole.deleteModel', [modelData.fullname, pk]).send().then(callback);
	}
};
