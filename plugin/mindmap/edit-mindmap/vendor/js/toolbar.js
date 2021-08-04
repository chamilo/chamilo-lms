kampfer.require('Component');
kampfer.require('events');
kampfer.require('Menu');

kampfer.provide('mindMap.ToolBar');

//TODO 构造器使用与menu相同的逻辑
kampfer.mindMap.ToolBar = kampfer.Component.extend({
	initializer : function(id) {
		var type = kampfer.type(id),
            that = this;

        if(type === 'string') {
            this._element = this._doc.getElementById(id);
            this._id = id;

            if(!this._element) {
                this.render();
            }
        } else {
            return;
        }

        this.resolve();
	},

    resolve : function() {
        if(this._element) {
            var triggers = this._element.querySelectorAll('[role=menu-trigger]');
            for(var i = 0, trigger; trigger = triggers[i]; i++) {
                var menu = trigger.querySelector('[role=menu]');
                this.addMenu(menu, trigger);
            }
        }
    },

	addMenu : function(menu, trigger) {
        var menu = new kampfer.Menu(menu, trigger);
        menu.getElement().id = menu.getId();
		this.addChild(menu, true);
	}
});