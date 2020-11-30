/*global kampfer console*/
kampfer.require('Component');
kampfer.require('dom');
kampfer.require('events');

kampfer.provide('mindMap.Caption');

kampfer.mindMap.Caption = kampfer.Component.extend({
    
    initializer : function(data) {
        kampfer.mindMap.Caption.superClass.initializer.apply(this, arguments);

        this._id = this.prefix + data.id;

        this.createDom();

        this._contentHolder = document.createElement('div');
        this._contentHolder.setAttribute('role', 'content');
        this._element.appendChild(this._contentHolder);

        this.setContent(data.content);

        this.isEditing = false;
    },
    
    decorate : function() {
        kampfer.mindMap.Caption.superClass.decorate.apply(this, arguments);
        
        this._element.className = 'node-caption blue';
        this._element.id = this._id;
        this._element.setAttribute('role', 'caption');
        this.fixPosition();
    },
    
    setContent : function(text) {
        this.hideTextarea();
        this.showContentHolder();
        this._contentHolder.innerHTML = text;
        this.isEditing = false;
    },
    
    getContent : function() {
        return this._contentHolder.innerHTML;
    },
    
    fixPosition : function() {
        var size = this.getSize();
        this.setPosition( -(size.width / 2), -(size.height / 2) );
    },
    
    insertTextarea : function() {
        var value = this.getContent();
        if(!this._textarea) {
            this._textarea = this._doc.createElement('textarea');
            this._textarea.value = value;
            this._textarea.id = 'node-editor';
            this._textarea.setAttribute('node-type', 'editor');
            this._textarea.className = 'node-editor';
            this._element.appendChild(this._textarea);
            this.isEditing = true;
        } else {
            this.showTextarea();
        }
        this.hideContentHolder();
    },

    showTextarea : function() {
        if(this._textarea) {
            this.isEditing = true;
            this._textarea.style.display = '';
        }
    },

    hideTextarea : function() {
        if(this._textarea) {
            this._textarea.style.display = 'none';
        }
    },

    showContentHolder : function() {
        this._contentHolder.style.display = '';
    },

    hideContentHolder : function() {
        this._contentHolder.style.display = 'none';
    },

    getTextareaValue : function() {
        if(this._textarea) {
            return this._textarea.value;
        }
    },
    
    prefix : 'caption-'
    
});