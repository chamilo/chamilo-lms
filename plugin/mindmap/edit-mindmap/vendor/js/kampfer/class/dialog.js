kampfer.require('class.UIComponent');
kampfer.require('dom');

kampfer.provide('Dialog');

kampfer.Dialog = kampfer.class.UIComponent.extend({
    initializer : function() {},

    createDom : function() {
        kampfer.Dialog.superClass.createDom.call(this);
        var element = this.getElement();

        //header
        this._headerElement = document.createElement('div');
        //title
        this._titleElement = document.createElement('h3');
        //close
        var closeButton = document.createElement('button');
        //body
        this._bodyElement = document.createElement('div');
        //footer
        this._footerElement = document.createElement('div');

        this._headerElement.appendChild(closeButton);
        this._headerElement.appendChild(this._titleElement);
        element.appendChild(this._headerElement);
        element.appendChild(this._bodyElement);
        element.appendChild(this._footerElement);

        kampfer.dom.addClass(this._element, 'modal');
        kampfer.dom.addClass(this._headerElement, 'modal-header');
        kampfer.dom.addClass(this._bodyElement, 'modal-body');
        kampfer.dom.addClass(this._footerElement, 'modal-footer');
        kampfer.dom.addClass(closeButton, 'close');

        closeButton.innerHTML = 'x';
        closeButton.setAttribute('data-action', 'close');

        if(!this._buttons) {
            this._footerElement.style.display = 'none';
        } else {
            for(var i = this._buttons.length - 1, buttonElment; (buttonElement = this._buttons[i]); i--) {
                kampfer.dom.addClass(buttonElement, 'btn');
                if(i === 0) {
                    kampfer.dom.addClass(buttonElement, 'btn-primary');
                }
                this._footerElement.appendChild(buttonElement);
            }
        }
    },

    setContent : function(html) {
        this._bodyElement.innerHTML = html;
    },

    getContent : function(html) {
        return this._bodyElement.innerHTML;
    },

    setTitle : function(title) {
        this._titleElement.innerHTML = title;
    },

    //modal居中.但是bootstrap貌似直接定死了modal的宽度然后用样式居中.
    reposition : function() {
        var winWidth = Math.max(document.documentElement.offsetWidth, document.body.offsetWidth),
            winHeight = Math.max(document.documentElement.offsetHeight, document.body.offsetHeight);

        this._element.style.left = kampfer.dom.scrollLeft(window) + winWidth / 2 -
            this._element.offsetWidth / 2 + 'px';
        this._element.style.top = kampfer.dom.scrollTop(window) + winHeight / 2 -
            this._element.offsetHeight / 2 + 'px';
    },

    show : function() {
        if(!this._element) {
            this.render();
        }
        this._element.style.display = '';
    },

    hide : function() {
        this._element.style.display = 'none';
    },

    dispose : function() {
        kampfer.Dialog.superClass.dispose.call(this);
        delete this._titleElement;
        delete this._headerElement;
        delete this._bodyElement;
        delete this._buttons;
        delete this._footerElement;
    }
});