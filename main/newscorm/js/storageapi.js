// Storage API
// JavaScript API
// CBlue SPRL, Jean-Karim Bockstael <jeankarim@cblue.be>


lms_storage_testCall = function(content) {
	alert(content);
}

lms_storage_setValue_user = function(sv_key, sv_value, sv_user) {
	var result;
	$.ajax({
		async: false,
		type: "POST",
		url: "storageapi.php",
		data: {
			action: "set",
			svkey: sv_key,
			svvalue: sv_value,
			svuser: sv_user,
			svcourse: sv_course,
			svsco: sv_sco
		},
		success: function(data) {
			result = (data != '0');
		}
	});
	return result;
}

lms_storage_getValue_user = function(sv_key, sv_user) {
	var result;
	$.ajax({
		async: false,
		type: "POST",
		url: "storageapi.php",
		data: {
			action: "get",
			svkey: sv_key,
			svuser: sv_user,
			svcourse: sv_course,
			svsco: sv_sco
		},
		success: function(data) {
			result = data;
		}
	});
	return result;
}

lms_storage_getAll_user = function(sv_user) {
	var result;
	$.ajax({
		async: false,
		type: "POST",
		url: "storageapi.php",
		data: {
			action: "getall",
			svuser: sv_user,
			svcourse: sv_course,
			svsco: sv_sco
		},
		success: function(data) {
			result = eval(data);
		}
	});
	return result;
}

lms_storage_stack_push_user = function(sv_key, sv_value, sv_user) {
	var result;
	$.ajax({
		async: false,
		type: "POST",
		url: "storageapi.php",
		data: {
			action: "stackpush",
			svvalue: sv_value,
			svkey: sv_key,
			svuser: sv_user,
			svcourse: sv_course,
			svsco: sv_sco
		},
		success: function(data) {
			result = (data != '0');
		}
	});
	return result;
}

lms_storage_stack_pop_user = function(sv_key, sv_user) {
	var result;
	$.ajax({
		async: false,
		type: "POST",
		url: "storageapi.php",
		data: {
			action: "stackpop",
			svkey: sv_key,
			svuser: sv_user,
			svcourse: sv_course,
			svsco: sv_sco
		},
		success: function(data) {
			result = data;
		}
	});
	return result;
}

lms_storage_stack_length_user = function(sv_key, sv_user) {
	var result;
	$.ajax({
		async: false,
		type: "POST",
		url: "storageapi.php",
		data: {
			action: "stacklength",
			svkey: sv_key,
			svuser: sv_user,
			svcourse: sv_course,
			svsco: sv_sco
		},
		success: function(data) {
			result = data;
		}
	});
	return result;
}

lms_storage_stack_clear_user = function(sv_key, sv_user) {
	var result;
	$.ajax({
		async: false,
		type: "POST",
		url: "storageapi.php",
		data: {
			action: "stackclear",
			svkey: sv_key,
			svuser: sv_user,
			svcourse: sv_course,
			svsco: sv_sco
		},
		success: function(data) {
			result = data;
		}
	});
	return result;
}

lms_storage_stack_getAll_user = function(sv_key, sv_user) {
	var result;
	$.ajax({
		async: false,
		type: "POST",
		url: "storageapi.php",
		data: {
			action: "stackgetall",
			svkey: sv_key,
			svuser: sv_user,
			svcourse: sv_course,
			svsco: sv_sco
		},
		success: function(data) {
			result = eval(data);
		}
	});
	return result;
}

lms_storage_getAllUsers = function() {
	var result;
	$.ajax({
		async: false,
		type: "POST",
		url: "storageapi.php",
		data: {
			action: "usersgetall"
		},
		success: function(data) {
			result = eval(data);
		}
	});
	return result;
}

lms_storage_setValue = function(sv_key, sv_value) {
	return lms_storage_setValue_user(sv_key, sv_value, sv_user);
}

lms_storage_getValue = function(sv_key) {
	return lms_storage_getValue_user(sv_key, sv_user);
}

lms_storage_getAll = function() {
	return lms_storage_getAll_user(sv_user);
}

lms_storage_stack_push = function(sv_key, sv_value) {
	return lms_storage_stack_push_user(sv_key, sv_value, sv_user);
}

lms_storage_stack_pop = function(sv_key) {
	return lms_storage_stack_pop(sv_key, sv_user);
}

lms_storage_stack_length = function(sv_key) {
	return lms_storage_stack_length_user(sv_key, sv_user);
}

lms_storage_stack_clear = function(sv_key) {
	return lms_storage_stack_clear_user(sv_key, sv_user);
}

lms_storage_stack_getAll = function(sv_key) {
	return lms_storage_stack_getAll_user(sv_key, sv_user);
}


// Accessor object
function STORAGEAPIobject() {
	this.testCall = lms_storage_testCall;
	this.setValue = lms_storage_setValue;
	this.setValue_user = lms_storage_setValue_user;
	this.getValue = lms_storage_getValue;
	this.getValue_user = lms_storage_getValue_user;
	this.getAll = lms_storage_getAll;
	this.getAll_user = lms_storage_getAll_user;
	this.stack_push = lms_storage_stack_push;
	this.stack_push_user = lms_storage_stack_push_user;
	this.stack_pop = lms_storage_stack_pop;
	this.stack_pop_user = lms_storage_stack_pop_user;
	this.stack_length = lms_storage_stack_length;
	this.stack_length_user = lms_storage_stack_length_user;
	this.stack_clear = lms_storage_stack_clear;
	this.stack_clear_user = lms_storage_stack_clear_user;
	this.stack_getAll = lms_storage_stack_getAll;
	this.stack_getAll_user = lms_storage_stack_getAll_user;
	this.getAllUsers = lms_storage_getAllUsers;
	this.sv_user = sv_user;
	this.sv_course = sv_course;
	this.sv_sco = sv_sco;
}
var STORAGEAPI = new STORAGEAPIobject();
var STAPI = STORAGEAPI;
