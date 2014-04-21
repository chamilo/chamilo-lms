{% extends "default/layout/main.tpl" %}

{% block body %}
	{# Main content #}

    {# Plugin main top #}
    {% if plugin_main_top %}
        <div id="plugin_main_top" class="span12">
            {{ plugin_main_top }}
        </div>
    {% endif %}

	{#  Right column #}
	<div class="span3 menu-column">
        {% if plugin_menu_top %}
            <div id="plugin_menu_top">
                {{plugin_menu_top}}
            </div>
        {% endif %}

	    {# if user is not login show the login form #}
		{% if _u.logged  == 0 %}
			{% include "default/layout/login_form.tpl" %}
		{% endif %}
        <div class="block_user_info">
		{# User picture #}
        {{ user_image_block }}
        

        {# User Profile links #}
		{{ profile_block }}
        </div>
        <div class="block_tools_info">
        {# Course block - admin #}
		{{ course_block }}

        {# Course block - teacher #}
		{{ teacher_block }}
        </div>
        <div class="user_notification">
		{# Notice #}
		{{ notice_block }}

        {# Help #}
		{{ help_block }}

		{# Links that are not added in the tabs #}
		{{ navigation_course_links }}

		{# Reservation block  #}
		{{ reservation_block }}

		{# Search (xapian) #}
		{{ search_block }}
        </div>
		{# Classes #}
		{{ classes_block }}

		{# Skills #}
		{{ skills_block }}
        
		{# Plugin courses sidebar #}
        {# Plugins for footer section #}

        {% if plugin_menu_bottom %}
            <div id="plugin_menu_bottom">
                {{ plugin_menu_bottom }}
            </div>
        {% endif %}
	</div>

	<div class="span9 content-column">
        {# Plugin bottom #}
        {% if plugin_content_top %}
            <div id="plugin_content_top">
                {{ plugin_content_top }}
            </div>
        {% endif %}

		{# Portal homepage #}
        {% if home_page_block %}
            <section id="homepage">
                <div class="row">
                    <div class="span9">
                    {{ home_page_block }}
                    </div>
                </div>
            </section>
        {% endif %}

		{#  ??  #}
		{{ sniff_notification }}

        {% include "default/layout/page_body.tpl" %}

        {# Welcome to course block  #}
        {% if welcome_to_course_block %}
            <section id="welcome_to_course">
            {{ welcome_to_course_block }}
            </section>
        {% endif %}

        {% if content is not null %}
            <section id="main_content">
                {{ content }}
            </section>
        {% endif %}

		{# Announcements  #}
        {% if announcements_block %}
            <section id="announcements">
            {{ announcements_block }}
            </section>
        {% endif %}

        {# Course categories (must be turned on in the admin settings) #}
        {% if course_category_block %}
            <section id="course_category">
                <div class="row">
                    <div class="span9">
                    {{ course_category_block }}
                    </div>
                </div>
            </section>
        {% endif %}

		{# Hot courses template  #}
		{% include "default/layout/hot_courses.tpl" %}

        {# Content bottom  #}
        {% if plugin_content_bottom %}
            <div id="plugin_content_bottom">
                {{plugin_content_bottom}}
            </div>
        {% endif %}
        &nbsp;
	</div>

    {# Plugin main bottom #}
    {% if plugin_main_bottom %}
        <div id="plugin_main_bottom" class="span12">
            {{ plugin_main_bottom }}
        </div>
    {% endif %}

{% endblock %}
