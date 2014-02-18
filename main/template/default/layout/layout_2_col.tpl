{% extends app.template_style ~ "/layout/main.tpl" %}

{% block body %}
	{# Main content #}

	{#  Left column  #}
	<div class="col-md-3 menu-column">
        {% block left_column %}

            {% if plugin_menu_top %}
                <div id="plugin_menu_top">
                    {{plugin_menu_top}}
                </div>
            {% endif %}

            {# if user is not login show the login form #}
            {% if _u.logged  == 0 %}
                {% include app.template_style ~ "/layout/login_form.tpl" %}
            {% endif %}

            {#  course_session_block #}
            {% include app.template_style ~ "/index/course_session_block.tpl" %}

            {#  User picture  #}
            {% include app.template_style ~ "/index/user_image_block.tpl" %}

            {#  User Profile links #}
            {% include app.template_style ~ "/index/profile_block.tpl" %}

            {#  Social links #}
            {% include app.template_style ~ "/index/profile_social_block.tpl" %}

            {#  Course block - admin #}
            {% include app.template_style ~ "/index/course_block.tpl" %}

            {#  Course block - teacher #}
            {% include app.template_style ~ "/index/teacher_block.tpl" %}

            {#  Session block #}
            {% include app.template_style ~ "/index/session_block.tpl" %}

            {#  Notice  #}
            {% include app.template_style ~ "/index/notice_block.tpl" %}

            {#  Help #}
            {% include app.template_style ~ "/index/help_block.tpl" %}

            {#  Links that are not added in the tabs #}
            {% include app.template_style ~ "/index/navigation_block.tpl" %}

            {#  Reservation block  #}
            {{ reservation_block }}

            {#  Search (xapian) #}
            {{ search_block }}

            {#  Classes  #}
            {{ classes_block }}

            {#  Skills #}
            {% include app.template_style ~ "/index/skills_block.tpl" %}

            {#  Plugin courses sidebar  #}
            {#  Plugins for footer section  #}

            {% if plugin_menu_bottom %}
                <div id="plugin_menu_bottom">
                    {{ plugin_menu_bottom }}
                </div>
            {% endif %}
        {% endblock %}
	</div>
	<div class="col-md-6 content-column">
        {% block right_column %}

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
                        <div class="col-md-9">
                        {{ home_page_block }}
                        </div>
                    </div>
                </section>
            {% endif %}

            {#  ??  #}
            {{ sniff_notification }}

            {% include app.template_style ~ "/layout/page_body.tpl" %}

            {% if content is not null %}
                <section id="main_content">
                    {{ content }}
                </section>
            {% endif %}

            {% include app.template_style ~ "/layout/page_post_body.tpl" %}

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
                        <div class="col-md-9">
                        {{ course_category_block }}
                        </div>
                    </div>
                </section>
            {% endif %}

            {#  Hot courses template  #}
            {% include app.template_style ~ "/layout/hot_courses.tpl" %}

            {#  Content bottom  #}
            {% if plugin_content_bottom %}
                <div id="plugin_content_bottom">
                    {{plugin_content_bottom}}
                </div>
            {% endif %}
        {% endblock %}
        &nbsp;
	</div>

{% endblock %}
