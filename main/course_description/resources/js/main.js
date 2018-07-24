function Proxy() {
}

Proxy.prototype.root = function () {
    return www + '/main/inc/ajax/course_description.ajax.php';
}

Proxy.prototype.post = function (data, f) {
    if (typeof(sec_token) !== 'undefined') {
        data.sec_token = sec_token;
    }
    $.post(this.root(), data, f, 'json');
}

var CourseDescription = new Proxy();

CourseDescription.del = function (c_id, id, f) {
    var data = {
        c_id: c_id,
        id: id,
        action: 'delete'
    };
    this.post(data, f);
};

CourseDescription.delete_by_course = function (c_id, session_id, f) {
    var data = {
        c_id: c_id,
        session_id: session_id,
        action: 'delete_by_course'
    };
    this.post(data, f);
};

var message = {};

message.update = function (data) {
    text = typeof(data) == 'string' ? data : data.message;
    $('#messages').html(text);
}