/*
 * jQuery Toggle
 */

$(document).ready(function(){
	//hide the all of the element with class session_course_item
	$(".session_box").show();
	$(".session_course_item").show();
	
	//toggle the componenet
	$("div.session_category_title_box").click(function(){
		
		category_image = $(this).attr("id").split("_");
		category_real_image_id = category_image[category_image.length-1];
		
		image_clicked = $("#category_img_"+category_real_image_id).attr("src");
		image_clicked_info = image_clicked.split("/");
		image_real_clicked = image_clicked_info[image_clicked_info.length-1];
		image_path = image_clicked.split("img");
		current_path = image_path[0]+"img/";
		
		if (image_real_clicked == 'div_show.gif') {
			current_path = current_path+'div_hide.gif';
			$("#category_img_"+category_real_image_id).attr("src", current_path);
		} else {
			current_path = current_path+'div_show.gif';
			$("#category_img_"+category_real_image_id).attr("src", current_path)
		}
		
		$(this).nextAll().slideToggle("fast");
		
	});
	
	$("li.session_box_title").click(function(){
		
		session_image = $(this).attr("id").split("_");
		session_real_image_id = session_image[session_image.length-1];
		
		image_clicked = $("#session_img_"+session_real_image_id).attr("src");
		image_clicked_info = image_clicked.split("/");
		image_real_clicked = image_clicked_info[image_clicked_info.length-1];
		image_path = image_clicked.split("img");
		current_path = image_path[0]+"img/";
		
		if (image_real_clicked == 'div_show.gif') {
			current_path = current_path+'div_hide.gif';
			$("#session_img_"+session_real_image_id).attr("src", current_path)
		} else {
			current_path = current_path+'div_show.gif';
			$("#session_img_"+session_real_image_id).attr("src", current_path)
		}
		
		$(this).nextAll().slideToggle("fast");
		
	});
	
});
