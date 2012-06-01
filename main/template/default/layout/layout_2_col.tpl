{% extends "default/layout/main.tpl" %}

{% block body %}	
	{# Main content #}
   
	<div class="span9">
        
        {#  Plugin bottom  #}
        {% if plugin_content_top %}
            <div id="plugin_content_top">
                {{ plugin_content_top }}
            </div>
        {% endif %}
        
		{#  ??  #}
        {% if home_page_block %}
            <section id="home_page">
                <div class="row">
                    <div class="span9">
                    {{ home_page_block}}
                    </div>
                </div>
            </section>
        {% endif %}
		
		{#  ??  #}
		{{ sniff_notification }}
		
        {% include "default/layout/page_body.tpl" %}
                
        {% if content is not null %}
            <section id="main_content">
                {{ content }}
            </section>
        {% endif %}
		
		{#  Announcements  #}
        {% if announcements_block %}      
            <section id="announcements_page">
            {{ announcements_block }}
            </section>
        {% endif %}
		
		{#  Hot courses template  #}		
		{% include "default/layout/hot_courses.tpl" %}
        
        {#  Content bottom  #}
        {% if plugin_content_bottom %}       
            <div id="plugin_content_bottom">
                {{plugin_content_bottom}}
            </div>
        {% endif %}
        &nbsp;
	</div>
		
	{#  Right column  #}
	<div class="span3">		        
        {% if plugin_menu_top %}
            <div id="plugin_menu_top">
                {{plugin_menu_top}}
            </div>
        {% endif %}  	
        
	    {# if user is not login show the login form #}
		{% if _u.logged  == 0 %}
			{% include "default/layout/login_form.tpl" %}
		{% endif %}

		{#  User picture  #}
		{{ profile_block }}
        
        {#  Course block - admin #}
		{{ course_block }}
        
        {#  Course block - teacher #}
		{{ teacher_block }}
		
		{#  Notice  #}
		{{ notice_block }}
                    
        {#  Help #}
		{{ help_block }}
		
		{#  Links that are not added in the tabs #}
		{{ navigation_course_links }}
		
		{#  Reservation block  #}
		{{ reservation_block }}
		
		{#  Search (xapian) #}
		{{ search_block }}
		
		{#  Classes  #}
		{{ classes_block }}
		
		{#  Skills #}
		{{ skills_block }}
        	
		{#  Plugin courses sidebar  #}		
        {#  Plugins for footer section  #}		
        
        {% if plugin_menu_bottom %}
            <div id="plugin_menu_bottom">
                {{ plugin_menu_bottom }}
            </div>
        {% endif %}        
	</div>
{% endblock %}