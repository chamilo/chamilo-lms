{% extends "default/layout/main.tpl" %}

{% block body %}
	{# Main content #}

	{#  Right column  #}
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

		{#  User picture  #}
        {% include "default/index/user_image_block.tpl" %}

        {#  User Profile links #}
        {% include "default/index/profile_block.tpl" %}

        {% include "default/index/profile_social_block.tpl" %}

        {#  Course block - admin #}
        {% include "default/index/course_block.tpl" %}

        {#  Course block - teacher #}
        {% include "default/index/teacher_block.tpl" %}

		{#  Notice  #}
        {% include "default/index/notice_block.tpl" %}

        {#  Help #}
		{% include "default/index/help_block.tpl" %}

		{#  Links that are not added in the tabs #}
		{{ navigation_course_links }}

		{#  Reservation block  #}
		{{ reservation_block }}

		{#  Search (xapian) #}
		{{ search_block }}

		{#  Classes  #}
		{{ classes_block }}

		{#  Skills #}
        {% include "default/index/skills_block.tpl" %}

		{#  Plugin courses sidebar  #}
        {#  Plugins for footer section  #}

        {% if plugin_menu_bottom %}
            <div id="plugin_menu_bottom">
                {{ plugin_menu_bottom }}
            </div>
        {% endif %}
	</div>
	<div class="span9 content-column">

        {#  Plugin bottom  #}
        {% if plugin_content_top %}
            <div id="plugin_content_top">
                {{ plugin_content_top }}
            </div>
        {% endif %}

		{#  Portal homepage  #}
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

        {#  Welcome to course block  #}
        {% if welcome_to_course_block %}
            <section id="welcome_to_course">
            {% include "default/layout/welcome_to_course.tpl" %}
            </section>
        {% endif %}

        {% if content is not null %}
            <section id="main_content">
                {{ content }}
            </section>
        {% endif %}

		{#  Announcements  #}
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

{% endblock %}
