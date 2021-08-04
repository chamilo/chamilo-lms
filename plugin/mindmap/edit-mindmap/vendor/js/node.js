/*global kampfer console*/
kampfer.require('events');
kampfer.require('dom');
kampfer.require('Component');
kampfer.require('mindMap.Branch');
kampfer.require('mindMap.Caption');

kampfer.provide('mindMap.Node');

kampfer.mindMap.Node = kampfer.Component.extend({
    
    initializer : function(data) {
        kampfer.mindMap.Node.superClass.initializer.apply(this, arguments);

        this._id = data.id;
        
        this.addCaption(data);
        this.addBranch(data);
        this.addChildren(data.children);

        this.createDom();
        this.setPosition(data.offset.x, data.offset.y);
    },
    
    addCaption : function(data) {
        var caption = new kampfer.mindMap.Caption(data);
        this.addChild(caption);
    },
    
    addBranch : function(data) {
        if(data.parent) {
            var branch = new kampfer.mindMap.Branch(data);
            this.addChild(branch);
        }
    },

    addChildren : function(children) {
        if(children) {
            for(var i = 0, l = children.length; i < l; i++) {
                var child = children[i];
                this.addChild( new kampfer.mindMap.Node(child) );
            }
        }
    },
    
    getBranch : function() {
        return this.getChild('branch-' + this._id);
    },
    
    getCaption : function() {
        return this.getChild('caption-' + this._id);
    },
    
    /* 拓展此方法 */
    decorate : function() {
        kampfer.mindMap.Node.superClass.decorate.apply(this, arguments);
        
        this._element.id = this._id;
        kampfer.dom.addClass(this._element, 'node');
        this._element.setAttribute('role', 'node');
    },
    
    move : function(x, y) {
        var oriPosition = this.getPosition();
        
        x += oriPosition.left;
        y += oriPosition.top;
        
        this.moveTo(x, y);
    },
    
    moveTo : function(x, y) {
        this.setPosition(x, y);
        //如果是node就同步更新branch视图
        if(this._parent._id !== 'map') {
            this.getBranch().decorate();
        }
    },

    isEditing : function() {
        return this.getCaption().isEditing;
    },

    dispose : function() {}
    
});