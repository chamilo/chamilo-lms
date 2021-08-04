kampfer.require('Dialog');
kampfer.require('mindMap.command');

kampfer.provide('mindMap.RenameMapDialog');

kampfer.mindMap.RenameMapDialog = kampfer.Dialog.extend({
    initializer : function(storage, view) {
        this._storage = storage;
        this._view = view;
    },

    events : {
        click : {
            'ok' : 'ok hide',
            'close' : 'hide',
            'cancel' : 'hide',
            map : function(event) {
                var element = event.target,
                    name = element.querySelectorAll('td')[1].innerHTML;
                kampfer.dom.addClass(element, 'info');
                this.selectMap(name);
            }
        }
    },

    _content : '<p class=\"text-info\">There are <span>0</span> maps in your localstorage.<\/p>' +
                '<div class=\"app-file-list\">' +
                    '<table class=\"table table-condensed table-hover table-striped\">' +
                        '<thead>' +
                            '<tr>' +
                                '<th>#<\/th>' +
                                '<th>map name<\/th>' +
                                '<th>lastModified<\/th>' +
                            '<\/tr>' +
                        '<\/thead>' +
                        '<tbody><\/tbody>' +
                    '<\/table>' +
                '<\/div>' +
                '<div class=\"app-file-name\">' +
                    '<div class=\"input-prepend\">' +
                        '<span class=\"add-on\">File name :<\/span>' +
                        '<input class=\"span4\" id=\"map-name\" type=\"text\" placeholder=\"Please write file name\">' +
                    '<\/div>' +
                '<\/div>',

    _buttons : (function() {
        var buttons = [
            document.createElement('button'),
            document.createElement('button')
        ];
        buttons[0].setAttribute('data-action', 'ok');
        buttons[0].innerHTML = 'Save';
        buttons[1].setAttribute('data-action', 'cancel');
        buttons[1].innerHTML = 'Cancel';
        return buttons;
    })(),

    render : function() {
        kampfer.mindMap.RenameMapDialog.superClass.render.call(this);
        this.setContent(this._content);
        this.setTitle('另存为:');
    },

    updateMapCount : function() {
        var mapCount = this._storage.getMapCount();
        if(!this._mapCountElement) {
            this._mapCountElement = this._element.querySelector('.text-info>span');
        }
        this._mapCountElement.innerHTML = mapCount;
    },

    updateMapList : function() {
        var mapList = this._storage.getMapList();
        if(!mapList) {
            return;
        }
        
        for(var i = 0, map; (map = mapList[i]); i++) {
            this.addMap2List(map, i + 1);
        }
    },

    addMap2List : function(map, index) {
        var tr = document.createElement('tr'),
            name = map.name,
            lastModified = new Date(map.lastModified).toLocaleDateString();

        tr.innerHTML = ['<td>', index, '</td><td>', name, '</td><td>', lastModified, '</td'].join('');
        tr.setAttribute('data-action', 'map');

        if(!this._mapListElment) {
            this._mapListElment = this._element.querySelector('tbody');
        }

        this._mapListElment.appendChild(tr);
    },

    selectMap : function(name) {
        this._element.querySelector('#map-name').value = name;
        this._selectedMap = this._storage.getMapData(name);
    },

    getSelectedMap : function() {
        return this._selectedMap;
    },

    ok : function() {
        this.dispatch('ok', this._element.querySelector('#map-name').value);
    },

    show : function() {
        kampfer.OpenMapDialog.superClass.show.call(this);
        this.updateMapCount();
        this.updateMapList();
    },

    dispose : function() {
        kampfer.OpenMapDialog.superClass.dispose.call(this);
        delete this._storage;
        delete this._view;
        delete this._buttons;
        delete this._mapListElment;
        delete this._mapCountElement;
    }
});