// JS interface enabling scorm content to use main/document/remote.php easily
// CBlue SPRL, Arnaud Ligot <arnaud@cblue.be>


lms_documents_list = function(path) {
	var result;
	$.ajax({
		async: false,
		type: "POST",
		datatype: "json",
		url: "../document/remote.php",
		data: {
			action: "list",
			cwd: path,
			cidReq: chamilo_courseCode,
		},
		success: function(data) {
			result = eval("("+data+")");
		}
	});
	return result;
}

// Accessor object
function DOCUMENTAPIobject() {
	this.list = lms_documents_list;
}
var DOCUMENTAPI = new DOCUMENTAPIobject();
