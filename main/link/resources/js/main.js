
function Proxy() {};

Proxy.prototype.root = function(){
    return www + '/main/inc/ajax/link.ajax.php';
}

Proxy.prototype.post = function(data, f){    
    if(typeof(sec_token)!=='undefined'){  
        data.sec_token = sec_token;
    }
    $.post(this.root(), data, f, 'json');
}

var Link = new Proxy();

Link.hide = function(c_id, id, f)
{
    var data = {
        c_id: c_id, 
        id: id, 
        action: 'hide_link'
    };
    this.post(data, f);
};

Link.show = function(c_id, id, f)
{
    var data = {
        c_id: c_id, 
        id: id, 
        action: 'show_link'
    };
    this.post(data, f);
};

Link.del = function(c_id, id, f)
{
    var data = {
        c_id: c_id, 
        id: id, 
        action: 'delete_link'
    };
    this.post(data, f);
};

Link.delete_by_course = function(c_id, session_id, f)
{
    var data = {
        c_id: c_id, 
        session_id: session_id, 
        action: 'delete_by_course'
    };
    this.post(data, f);
};

Link.sort = function(c_id, ids, f){
    var data = {
        c_id: c_id, 
        ids: ids, 
        action: 'sort_links'
    };
    this.post(data, f);
};

Link.validate = function(c_id, id, f)
{
    var data = {
        c_id: c_id, 
        id: id, 
        action: 'validate_link'
    };
    this.post(data, f);
};



var LinkCategory = new Proxy();

LinkCategory.del = function(c_id, id, f)
{
    var data = {
        c_id: c_id, 
        id: id, 
        action: 'delete_category'
    };
    this.post(data, f);
};

LinkCategory.sort = function(c_id, ids, f){
    var data = {
        c_id: c_id, 
        ids: ids, 
        action: 'sort_categories'
    };
    this.post(data, f);
};


var message = {};

message.update = function(data){
    text = typeof(data)=='string' ? data : data.message;
    $('#messages').html(text)
}