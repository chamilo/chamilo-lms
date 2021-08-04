kampfer.require('events.EventTarget');

kampfer.provide('class.Composition');

kampfer.class.Composition = kampfer.events.EventTarget.extend({
    _id : null,

    _parent : null,
    
    //array
    //_children必须与_childrenIndex同步
    //懒加载
    _children : null,
    
    //object
    //_childrenIndex必须与_children同步
    //懒加载
    _childrenIndex : null,

    //递归调用子节点的指定方法
    //实现composition模式的关键方法之一
    walk : function(method) {
        var args = Array.prototype.slice.call(arguments, 1);
        this.forEachChild(function(child, i) {
            if( kampfer.type(child[method]) === 'function' ) {
                child[method].apply(child, args);
            }
        });
    },
    
    getId : function() {
        return this._id ||
            ( this._id = kampfer.Composition.generateUniqueId() );
    },
    
    setId : function(id) {
        if(this._parent && this._parent._childrenIndex) {
            delete this._parent._childrenIndex[this._id];
            this._parent._childrenIndex[id] = this;
        }

        this._id = id;
    },
    
    getParent : function() {
        return this._parent;
    },
    
    setParent : function(parent) {
        //新的parent不为空时，必须是component实例
        if( parent && !(parent instanceof kampfer.UIComponent) ) {
            return;
        }

        //新的parent不能是对象自己
        if(parent === this) {
            return;
        }

        //对象已经是另一个对象的child，那么必须先调用removeChild之后再调用setParent
        //对象不可能同时是另外两个对象的child
        if( parent && this._parent && this._id &&
            this._parent.getChild(this._id) && parent !== this._parent ) {
            return;
        }

        this._parent = parent;
        this.setParentEventTarget(parent);

        //对象不是新parent的child，那么将child添加到parnet的子列表中
        //因为addchild方法会检查_parent属性，所以必须在设置完_parent属性后才能执行添加操作
        //closure没有这一步, 它的setParent方法只保证child的parent属性正确,
        //但不保证child一定在parnet的子节点列表中
        if( parent && !parent.getChild(this._id) ) {
            parent.addChild(this);
        }
    },
    
    addChild : function(child, render) {
        this.addChildAt(child, this.getChildCount(), render);
    },
    
    addChildAt : function(child, index, render) {
        if( !(child instanceof kampfer.UIComponent) ) {
            return;
        }

        if(index < 0 || index > this.getChildCount() ) {
            return;
        }

        if(!this._children || !this._childrenIndex) {
            this._children = [];
            this._childrenIndex = {};
        }

        if( child.getParent() === this ) {
            //删除_children中保存的child引用
            //避免_children中保存多个child引用
            for(var i = 0, c; (c = this._children[i]); i++) {
                if(c === child) {
                    this._children.splice(i, 1);
                }
            }
        }
        this._childrenIndex[child.getId()] = child;
        this._children.splice(index, 0, child);

        //closure没有这一步, 它的addChildAt方法只保证child在parent的子节点列表中,
        //不保证child的parent一定是this. 这里我尝试增加这种确定性.
        if(child._parent !== this) {
            child.setParent(this);
        }
    },
    
    getChild : function(id) {
        if(id && this._childrenIndex) {
            return this._childrenIndex[id];
        }
    },
    
    getChildAt : function(index) {
        if(this._children) {
            return this._children[index];
        }
    },
    
    removeChild : function(child) {
        if(child) {
            var id;
            if( kampfer.type(child) === 'string' ) {
                id = child;
                child = this.getChild(id);
            } else {
                id = child.getId();
            }

            for(var i = 0, c; (c = this._children[i]); i++) {
                if(c === child) {
                    this._children.splice(i, 1);
                }
            }
            delete this._childrenIndex[id];

            child.setParent(null);
        }

        return child;
    },
    
    removeChildAt : function(index) {
        this.remochild( this.getChildAt(index) );
    },
    
    forEachChild : function(callback, context) {
        if(!this._children) {
            return;
        }
        for(var i = 0, child; (child = this._children[i]); i++) {
            if( calllback.call(context || child, child, i) === false ) {
                return;
            }
        }
    },
    
    indexOfChild : function(child) {
        this.forEachChild(function(c, i) {
            if(c === child) {
                return i;
            }
        });
    },
    
    getChildCount : function() {
        if(this._children) {
            return this._children.length;
        }
    },

    dispose : function() {
        kampfer.Composition.superClass.dispose.call(this);
        delete this._parent;
        delete this._children;
        delete this._childrenIndex;
    }
});

kampfer.Composition.generateUniqueId = function() {
    var guid = "";
    for(var i = 1; i <= 32; i++) {
        var n = Math.floor(Math.random() * 16.0).toString(16);
        guid += n;
        if((i == 8) || (i == 12) || (i == 16) || (i == 20)) {
            guid += "-";
        }
    }
    return guid;
};