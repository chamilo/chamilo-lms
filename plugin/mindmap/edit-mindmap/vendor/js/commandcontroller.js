/*global kampfer*/
kampfer.require('Class');
kampfer.require('mindMap.command');
kampfer.require('mindMap.map');
kampfer.require('mindMap.mapManager');
kampfer.require('mindMap.command.stack');
kampfer.require('mindMap.radio');
kampfer.require('mousetrap');

kampfer.provide('mindMap.command.Controller');

//暂时长这样吧#_#
//以后再改
kampfer.mindMap.command.Controller = kampfer.Class.extend({
    initializer : function(window) {
        this.view = window;

        kampfer.mindMap.radio.addListener('executeCommand', this.doCommand, this);

        var that = this;
        for(var name in kampfer.mindMap.command) {
            var command = kampfer.mindMap.command[name];

            if(command.shortcut) {
                if(!this._shortcut2Command) {
                    this._shortcut2Command = {};
                }

                this._shortcut2Command[command.shortcut] = name;

                var that = this;
                Mousetrap.bind(command.shortcut, function(event, shortcut) {
                    event.type = 'executeCommand';
                    event.command = that._shortcut2Command[shortcut];
                    that.doCommand(event);
                    return false;
                });
            }
        }
    },

    getCommand : function(name) {
        return kampfer.mindMap.command[name] ||
            kampfer.mindMap.command.Base;
    },

    doCommand : function(event) {
        var Command = kampfer.mindMap.command[event.command], command;
        if( Command && (!Command.isAvailable || Command.isAvailable()) ) {
            command = new Command(event, this.view);
            command.execute();
            if(command.needPush) {
                kampfer.mindMap.command.stack.push(command);
            } else {
                command.dispose();
            }
        }

        if(kampfer.mindMap.mapManager) {
            if( kampfer.mindMap.mapManager.isModified() ) {
                document.title = '*' + kampfer.mindMap.mapManager.getMapName();
            } else {
                document.title = kampfer.mindMap.mapManager.getMapName();
            }
        }
    },

    isCommandAvalilable : function(command) {
        command = this.getCommand(command);
        if( command.isAvailable && !command.isAvailable() ) {
            return false;
        } else {
            return true;
        }
    },

    _shortcut2Command : null,

    dispose : function() {
        kampfer.mindMap.CommandController.superClass.dispose.apply(this, arguments);
        this.publishers = null;
        this.commandStack = null;
    }
});