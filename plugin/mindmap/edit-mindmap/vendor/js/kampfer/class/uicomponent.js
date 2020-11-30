kampfer.require('class.Composition');
kampfer.require('events');

kampfer.provide('class.UIComponent');

kampfer.class.UIComponent = kampfer.class.Composition.extend({
    _element : null,

    _inDocument : false,

    /**
     * @type {object}
     */
    events : null,

    isInDocument : function() {
        return this._inDocument;
    },

    setElement : function() {
        this._element = element;
    },

    getElement :  function() {
        return this._element;
    },

    createDom : function() {
        this._element = document.createElement('div');
        return this._element;
    },

    //component有两种初始化的方式:
    //1.动态生成
    //2.传入已有dom,component解析
    //decorate方法就是针对第二种方式处理解析和预处理逻辑
    decorate : function(element) {},

    enterDocument : function() {
        this._inDocument = true;

        var that = this;
        if(this.events) {
            for(var attr in this.events) {
                kampfer.events.addListener(this._element, attr, this._transition, this);
            }
        }

        this.walk('enterDocument');
    },

    _transition : function(event) {
        var element = event.target,
            handlers = this.events[event.type],
            action = element.getAttribute('data-action');

        while( !action && (element = element.parentNode) ) {
            if(element.getAttribute) {
                action = element.getAttribute('data-action');
            }
        }

        if( !action || !handlers || !(action in handlers) ) {
            return;
        }

        event.target = element;

        if(typeof handlers[action] === 'string') {
            handlers = handlers[action].split(' ');
            for(var i = 0, handle; (handle = handlers[i]); i++) {
                if( this[handle] && this[handle](event) === false ) {
                    return false;
                }
            }
        } else if(typeof handlers[action] === 'function') {
            handlers[action].call(this, event);
        }
    },

    exitDocument : function() {
        this._inDocument = true;

        kampfer.events.removeListener(this._element);

        this.walk('exitDocument');
    },

    render : function(parentElement, beforeNode) {
        if(this._inDocument) {
            return;
        }

        if(!this._element) {
            this.createDom();
        }

        if(parentElement) {
            parentElement.insertBefore(this._element, beforeNode || null);
        } else {
            document.body.appendChild(this._element);
        }

        //父component存在,但是它不在document中,那么子component不进入document
        if( !this._parent || this._parent.isInDocument() ) {
            this.enterDocument();
        }
    },

    addChild : function(child, render) {
        this.addChildAt(child, this.getChildCount(), render);
    },

    addChildAt : function(child, index, render) {
        kampfer.UIComponent.superClass.addChildAt.call(this, child, index);

        if( child._inDocument && this._inDocument && child.getParent() === this ) {
            var parentElement = this.getElement();
            parentElement.insertBefore( child.getElement(),
                (parentElement.childNodes[index] || null) );
        } else if(render) {
            if (!this._element) {
                this.createDom();
            }
            var sibling = this.getChildAt(index + 1);
            child.render_(this.getElement(), sibling ? sibling._element : null);
        }
    },

    removeChild : function(child, unrender) {
        kampfer.UIComponent.superClass.removeChild.call(this, child);

        if(unrender) {
            child.exitDocument();
            if( child._element ) {
                child._element.parentNode.removeChild(child._element);
            }
        }
    },

    removeChildAt : function(index, unrender) {
        this.remochild( this.getChildAt(index), unrender );
    },

    dispose : function() {
        kampfer.UIComponent.superClass.dispose.call(this);
        this.exitDocument();
        delete this._element;
    }
});