<?php

/* For licensing terms, see /license.txt */

exit;
require_once __DIR__.'/../inc/global.inc.php';
require_once __DIR__.'/tutor_report.lib.php';

$htmlHeadXtra[] = '<script>
$(function() {
    $(".ajax").click(function() {
        var url     = this.href;
        var dialog  = $("#dialog");
        if ($("#dialog").length == 0) {
                dialog  = $("'.'<div id="dialog" style="display:hidden"></div>'.'").appendTo("body");
        }

        // load remote content
        dialog.load(
            url,
            {},
            function(responseText, textStatus, XMLHttpRequest) {
                dialog.dialog({
                        modal	: true,
                        width	: 540,
                        height	: 400
                });
            }
        );
        //prevent the browser to follow the link
        return false;
    });
});


function showContent(div){
	if($("div#"+div).attr("class")=="blackboard_hide"){
		$("div#"+div).attr("class","blackboard_show");
		$("div#"+div).attr("style","");
	}else{
		$("div#"+div).attr("class","blackboard_hide");
		$("div#"+div).attr("style","");
	}

}

function save() {
	work_id = $("#work_id").val();
	forum_id = $("#forum_id").val();
	rs_id = $("#rs_id").val();
	 $.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(myObject) {
		$("div#confirmation").html("<img src=\"'.api_get_path(WEB_LIBRARY_PATH).'javascript/indicator.gif\" />"); },
		type: "POST",
		url: "update_report.php",
		data: "work_id="+work_id+"&forum_id="+forum_id+"&rs_id="+rs_id,
		success: function(data) {
			$("div#confirmation").html(data);
			 location.reload();
		}
	});
}
</script>
<style>
.blackboard_show {
	float:left;
	position:absolute;
	border:1px solid black;
	width: 350px;
	background-color:white;
	z-index:99; padding: 3px;
	display: inline;
}
.blackboard_hide {
	display: none;
}
.reports{
	border:1px ;
}
.reports th {
    border-bottom: 1px solid #DDDDDD;
    line-height: normal;
    text-align: center;
    vertical-align: middle;
    background-color: #F2F2F2;
}
</style>';

$course_code = api_get_course_id();
$results = initializeReport($course_code);
if (isset($_GET['action'])) {
    Export::arrayToXls($results['export'], "COURSE_USER_REPORT".$course_code);
} else {
    Display::display_header();
    api_protect_course_script();
    if (!api_is_allowed_to_edit()) {
        api_not_allowed();
    }
    echo $results['show'];
    Display::display_footer();
}
