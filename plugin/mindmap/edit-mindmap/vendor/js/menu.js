kampfer.require('Component');
kampfer.require('dom');
kampfer.require('events');
kampfer.require('mindMap.radio');

kampfer.provide('Menu');
kampfer.provide('MenuItem');

kampfer.Menu = kampfer.Component.extend({

    /*
     * @param {dom||string}elem 传入的elem参数是对象, 说明menu已经在文档流中,
     *      不需要再创建全新的dom对象插入文档流。传入的elem是字符串，那么Menu
     *      类将创建新的dom并插入文档，elem将作为menu的id使用。
     */
    initializer : function(elem, trigger) {
        kampfer.Menu.superClass.initializer.apply(this, arguments);

        var type = kampfer.type(elem);

        if(type === 'string') {
            this._id = elem;
            this.render();
        } else if(type === 'object' && elem.nodeType) {
            this._element = elem;
            this._id = elem.id;
            this._wasDecorated = true;
            this._inDocument = true;
        } else {
            return;
        }

        this._element.style.display = 'none';

        kampfer.events.addListener(this._element, 'click', function(event) {
            var command = event.target.getAttribute('command');
            //点击菜单后菜单项自动获得焦点并且高亮显示,我们不需要这种效果,
            //所以这里使菜单项失去焦点
            event.target.blur();
            //如果菜单项绑定了命令并且没有被禁用就触发相应事件
            if( command && !(/disabled/.test(event.target.parentNode.className)) ) {
                this.hide();
                event.type = 'executeCommand';
                event.command = command;
                kampfer.mindMap.radio.dispatch(event);
                return false;
            }
        }, this);

        if(trigger && trigger.nodeType) {
            this.trigger = trigger;

            //trigger的子元素的mouseover&mouseout冒泡到trigger上导致处理函数重复触发
            //webkit浏览器不支持mouseenter和mouseleave, 无法使用. 这里使用hook处理处理函数重复触发的问题
            kampfer.events.addListener(trigger, 'mouseover', function(event) {
                var relatedElement = event.relatedTarget;
                if( !kampfer.dom.contains(trigger, relatedElement) ) {
                    this.show();
                }
            }, this);

            kampfer.events.addListener(trigger, 'mouseout', function(event) {
                var relatedElement = event.relatedTarget;
                if( !kampfer.dom.contains(trigger, relatedElement) ) {
                    this.hide();
                }
            }, this);
        }
    },

    trigger : null,

    show : function() {
        this.dispatch('beforemenushow', this);
        kampfer.Menu.superClass.show.apply(this);
    },

    disable : function(index) {
        if(typeof index === 'number') {
            var commandItems = this._element.querySelectorAll('[command]');
            kampfer.dom.addClass(commandItems[index].parentNode, 'disabled');
        }
    },

    enable : function(index) {
        if(typeof index === 'number') {
            var commandItems = this._element.querySelectorAll('[command]');
            kampfer.dom.removeClass(commandItems[index].parentNode, 'disabled');
        }
    },

    dispose : function() {
        kampfer.Menu.superClass.dispose.apply(this, arguments);
        kampfer.events.removeListener(this._element);
        kampfer.events.removeListener(this.trigger);
    }

});