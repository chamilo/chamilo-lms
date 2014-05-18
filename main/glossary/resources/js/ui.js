/**
 * User interface objects.
 */

var message = {};

message.update = function(data){
    var text = typeof(data)=='string' ? data : data.message;
    $('#messages').html(text);
}

message.error = function(data){
    text = typeof(data)=='string' ? data : data.message;
    if(! text){
        return;
    }
    $('#messages').html('<div class="error-message">' + text + '</div>');
}

message.info = function(data){
    text = typeof(data)=='string' ? data : data.message;
    if(! text){
        return;
    }
    $('#messages').html('<div class="normal-message">' + text + '</div>');
}

message.confirmation = function(data){
    text = typeof(data)=='string' ? data : data.message;
    if(! text){
        return;
    }
    $('#messages').html('<div class="confirmation-message">' + text + '</div>');
}

message.warning = function(data){
    text = typeof(data)=='string' ? data : data.message;
    if(! text){
        return;
    }
    $('#messages').html('<div class="warning-message">' + text + '</div>');
}


var ui = {};

ui.message = message;

ui.loading = function(btn){
    $(btn).addClass("loading");
};

ui.done = function(btn){
    $(btn).removeClass("loading");
};

ui.confirm = function(){
    if(!window.confirm(lang.ConfirmYourChoice)){
        return false;
    } else {
        return true;
    }
};

ui.remove = function(name, btn){   
    if(!this.confirm()){
        return false;
    } 

    var item = $('#'+name);
    var id = item.attr('data-id'); 
    var c_id = item.attr('data-c_id'); 

    var f = function(data){
        if(data.success){
            item.remove();
        }
        message.update(data);
        ui.done(btn);
    };
    ui.loading(btn);
    ui.proxy.remove(c_id, id, f);
};

ui.remove_by_course = function(name, btn){
    if(!this.confirm()){
        return false;
    } 
    
    var item = $('#'+name);
    var c_id = item.attr('data-c_id'); 
    var session_id = item.attr('data-session_id'); 

    var f = function(data){
        if(data.success){
            item.remove();
            var wrapper = $('.dataTables_wrapper');
            wrapper.remove();
        }
        message.update(data);
        ui.done(btn);
    };
    ui.loading(btn);
    ui.proxy.remove_by_course(c_id, session_id, f);
    
};