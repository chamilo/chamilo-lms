kampfer.require('mindMap.Window');
kampfer.require('mindMap.ToolBar');
kampfer.require('mindMap.command.Controller');
kampfer.require('Menu');
kampfer.require('events');

kampfer.provide('mindMap');
kampfer.provide('mindMap.window');
kampfer.provide('mindMap.toolBar');
kampfer.provide('mindMap.nodeContextMenu');
kampfer.provide('mindMap.contextMenu');

kampfer.mindMap.init = function() {
    var nodeContextMenu = document.getElementById('node-context-menu'),
        contextMenu = document.getElementById('context-menu');

    kampfer.mindMap.toolBar = new kampfer.mindMap.ToolBar('app-tool-bar');
    kampfer.mindMap.window = new kampfer.mindMap.Window('map-container');
    kampfer.mindMap.nodeContextMenu = new kampfer.Menu(nodeContextMenu);
    kampfer.mindMap.contextMenu = new kampfer.Menu(contextMenu);

    kampfer.mindMap.command.controller = new kampfer.mindMap.command.Controller(kampfer.mindMap.window);

    function checkMenuCommand(event) {
        var commands = event.currentTarget.getElement().querySelectorAll('[command]');
        for(var i = 0, command; (command = commands[i]); i++) {
            var name = command.getAttribute('command');
            if( !kampfer.mindMap.command.controller.isCommandAvalilable(name) ) {
                this.disable(i);
            } else {
                this.enable(i);
            }
        }
    }

    kampfer.mindMap.toolBar.eachChild(function(child) {
        child.addListener('beforemenushow', checkMenuCommand);
    });
    kampfer.mindMap.contextMenu.addListener('beforemenushow', checkMenuCommand);
    kampfer.mindMap.nodeContextMenu.addListener('beforemenushow', checkMenuCommand);

    kampfer.events.addListener(window, 'beforeunload', function(event) {
        if( kampfer.mindMap.mapManager && kampfer.mindMap.mapManager.isModified() ) {
            event.returnValue = 'map未保存,确定退出?';
            return 'map未保存,确定退出?';
        }
    });
};