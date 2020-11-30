kampfer.require('mindMap.command');

kampfer.provide('mindMap.command.stack');
kampfer.provide('mindMap.command.Redo');
kampfer.provide('mindMap.command.Undo');

kampfer.mindMap.command.stack = {
    _queue : [],

    // <index 是execute过的命令
    // >=index 是被unExecute过的命令
    _index : 0,

    _maxLength : 50,

    push : function(command) {
        if(this._queue.length >= this._maxLength) {
            this._queue.shift().dispose();
            this._index--;
        }
        //this._queue.push(command);
        //this._index++;
        this._queue.splice(this._index++, 0, command);
    },

    stepForward : function() {
        if( !this.atEnd() ) {
            return this._queue[this._index++];
        }
    },

    stepBackward : function() {
        if( !this.atStart() ) {
            this._index--;
            return this._queue[this._index];
        }
    },

    atEnd : function() {
        if(this._index === this._queue.length) {
            return true;
        }
        return false;
    },

    atStart : function() {
        if(this._index === 0) {
            return true;
        }
        return false;
    },

    get : function(index) {
        if(index > 0 && index <= this._queue.length) {
            return this._queue[index];
        }
     },

    getStackLength : function() {
        return this._queue.length;
    },

    getStackIndex : function() {
        return this._index;
    },

    isEmpty : function() {
        return this._queue.length === 0;
    }
};


kampfer.mindMap.command.Undo = kampfer.mindMap.command.Base.extend({
    execute : function(level) {
        var kmcs = kampfer.mindMap.command.stack;
        level = level || this.level;

        for(var i = 0; i < this.level; i++) {
            var command = kmcs.stepBackward();
            if(command) {
                command.unExecute();
            }
        }
    },

    level : 1
});

kampfer.mindMap.command.Undo.isAvailable = function() {
    if( kampfer.mindMap.command.stack.isEmpty() ||
        kampfer.mindMap.command.stack.atStart() ) {
        return false;
    }
    return true;
};

kampfer.mindMap.command.Undo.shortcut = 'ctrl+z';


kampfer.mindMap.command.Redo = kampfer.mindMap.command.Base.extend({
    execute : function(level) {
        var kmcs = kampfer.mindMap.command.stack;
        level = level || this.level;

        for(var i = 0; i < this.level; i++) {
            var command = kmcs.stepForward();
            if(command) {
                command.execute();
            }
        }
    },

    level : 1
});

kampfer.mindMap.command.Redo.isAvailable = function() {
    if( kampfer.mindMap.command.stack.isEmpty() ||
        kampfer.mindMap.command.stack.atEnd() ) {
        return false;
    }
    return true;
};

kampfer.mindMap.command.Redo.shortcut = 'ctrl+y';