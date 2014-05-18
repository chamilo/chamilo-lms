{#  This template is NOT used in chamilo 1.9 #}
{{ extends "default/layout/main.tpl" }}

{% block body %}	
	<div id="maincontent" class="maincontent">
		{{ plugin_courses_block}}
		{{ home_page_block}}
		{{ message}}
		{{ content}}
		{{ announcements_block}}
	</div>
	
	<div id="menu-wrapper">	
		{{ login_block}}		
		{{ profile_block}}			
		{{ account_block}}	
		{{ teacher_block}}
		{{ notice_block}}
		{{ navigation_course_links}}
		{{ plugin_courses_right_block}}
		{{ reservation_block}}
		{{ search_block}}
		{{ classes_block}}
	</div>
{% endblock %}