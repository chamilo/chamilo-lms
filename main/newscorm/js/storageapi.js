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
			action: "usersgetall",
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

