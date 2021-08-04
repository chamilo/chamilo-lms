kampfer.require('Component');
kampfer.require('events');
kampfer.require('Menu');
kampfer.require('dom');

kampfer.provide('mindMap.Window');


//TODO 构造器使用与menu相同的逻辑
kampfer.mindMap.Window = kampfer.Component.extend({
    initializer : function(id) {
        var type = kampfer.type(id),
            that = this, scrollX, scrollY, x, y;

        if(type === 'string') {
            this._element = this._doc.getElementById(id);
            this._id = id;

            if(!this._element) {
                this.render();
            }
        } else {
            return;
        }

        this.beDraged = false;

        kampfer.events.addListener(this._element, 'mousedown', function(event) {
            scrollX = kampfer.dom.scrollLeft(that._element);
            scrollY = kampfer.dom.scrollTop(that._element);
            x = event.pageX;
            y = event.pageY;

            if(event.which === 1) {
                //不存在map或已经开始拖拽不执行处理逻辑
                if( !that.beDraged && kampfer.mindMap.map && event.target.getAttribute('role') === 'map') {
                    that.beDraged = true;
                }
            }
        });

        kampfer.events.addListener(this._element, 'mouseup', function(event) {
            if(that.beDraged) {
                that.beDraged = false;
                return false;
            }
        });

        kampfer.events.addListener(this._element, 'mousemove', function(event) {
            if(that.beDraged) {
                that.scrollLeft(scrollX + x - event.pageX);
                that.scrollTop(scrollY + y - event.pageY);

                return false;
            }
        });

        kampfer.events.addListener(this._element, 'contextmenu', function(event) {
            var role = event.target.getAttribute('role'),
                scrollX = kampfer.mindMap.window.scrollLeft(),
                scrollY = kampfer.mindMap.window.scrollTop();

            var menu;
            if(role === 'content' || role === 'caption' || role === 'node') {
                menu = kampfer.mindMap.nodeContextMenu;
            } else if(role === 'map') {
                menu = kampfer.mindMap.contextMenu;
            }

            if(menu) {
                menu.setPosition(event.pageX + scrollX, event.pageY + scrollY);
                menu.show();
            }

            return false;
        });

        kampfer.events.addListener(this._element, 'click', function() {
            kampfer.mindMap.contextMenu.hide();
            kampfer.mindMap.nodeContextMenu.hide();
        });
    },

    scrollLeft : function(offset) {
        var offsetX = kampfer.dom.scrollLeft(this._element, offset);
        if(typeof offsetX === 'number') {
            return offsetX;
        }
    },

    scrollTop : function(offset) {
        var offsetY = kampfer.dom.scrollTop(this._element, offset);
        if(typeof offsetY === 'number') {
            return offsetY;
        }
    },

    beDraged : null,

    dispose : function() {
        kampfer.mindMap.Window.superClass.dispose.apply(this);
        kampfer.events.removeListener(this._element);
    }
});