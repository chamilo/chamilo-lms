/**
 * Define a client proxy for ajax calls.
 */


function Proxy() {};

Proxy.prototype.root = function(){
    return context.ajax;
}

Proxy.prototype.post = function(data, f){    
    if(typeof(context)!=='undefined' && typeof(context.sec_token)!=='undefined'){  
        data.sec_token = context.sec_token;
    }
    $.post(this.root(), data, f, 'json');
}


var notebook = new Proxy();

notebook.remove = function(c_id, id, f)
{
    var data = {
        c_id: c_id, 
        id: id, 
        action: 'remove'
    };
    this.post(data, f);
};

notebook.remove_by_course = function(c_id, session_id, f)
{
    var data = {
        c_id: c_id, 
        session_id: session_id, 
        action: 'remove_by_course'
    };
    this.post(data, f);
};
