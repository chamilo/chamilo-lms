// Storage API
// JavaScript API

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
