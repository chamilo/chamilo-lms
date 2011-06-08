// Storage API
// JavaScript API
// CBlue SPRL, Jean-Karim Bockstael <jeankarim@cblue.be>


lms_storage_testCall = function(content) {
	alert(content);
}

lms_storage_setValue = function(sv_key, sv_value) {
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

lms_storage_getValue = function(sv_key) {
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

lms_storage_getAll = function() {
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

lms_storage_stack_push = function(sv_key, sv_value) {
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

lms_storage_stack_pop = function(sv_key) {
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

lms_storage_stack_length = function(sv_key) {
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

lms_storage_stack_clear = function(sv_key) {
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

lms_storage_stack_getAll = function(sv_key) {
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

// STORAGEAPI OBJECT
function STORAGEAPIobject() {
  this.testCall = lms_storage_testCall ;
  this.setValue = lms_storage_setValue;
  this.getValue = lms_storage_getValue;
  this.getAll = lms_storage_getAll;
  this.stack_push = lms_storage_stack_push;
  this.stack_pop = lms_storage_stack_pop;
  this.stack_length = lms_storage_stack_length;
  this.stack_clear = lms_storage_stack_clear;
  this.stack_getAll = lms_storage_stack_getAll;
  this.sv_user = sv_user;
  this.sv_course = sv_course;
  this.sv_sco = sv_sco;
}
var STORAGEAPI = new STORAGEAPIobject();
var STAPI = STORAGEAPI;
