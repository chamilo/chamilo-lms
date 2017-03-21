{% extends template ~ "/layout/page.tpl" %}

{% block body %}
<div class="row">
    {% if plugin_main_top %}
        <div class="page-main-top" class="col-md-12">
            {{ plugin_main_top }}
        </div>
    {% endif %}
    <div class="col-md-3">
        <div class="sidebar">
            {% if plugin_menu_top %}
                <div class="siderbar-menu-top">
                    {{ plugin_menu_top }}
                </div>
            {% endif %}

            {% include template ~ "/layout/login_form.tpl" %}

            {% if _u.logged  == 1 %}
                {{ user_image_block }}
            {% endif %}

            {{ profile_block }}
            {{ course_block }}
            {{ teacher_block }}
            {{ skills_block }}
            {{ certificates_search_block }}
            {{ notice_block }}
            {{ help_block }}
            {{ navigation_course_links }}
            {{ search_block }}
            {{ classes_block }}

            {% if plugin_menu_bottom %}
                <div class="sidebar-menu-bottom">
                    {{ plugin_menu_bottom }}
                </div>
            {% endif %}
        </div>
    </div>
    <div class="col-md-9">
        <div class="page-content">

            {% if plugin_content_top %}
                <div class="page-content-top">
                    {{ plugin_content_top }}
                </div>
            {% endif %}

            {{ sniff_notification }}

            {% if home_page_block %}
                <article id="homepage-home">
                    {{ home_page_block }}
                </article>
            {% endif %}

            {% block page_body %}
                {% include template ~ "/layout/page_body.tpl" %}
            {% endblock %}

            {% if welcome_to_course_block %}
                <article id="homepage-course">
                {{ welcome_to_course_block }}
                </article>
            {% endif %}

            {% block content %}
                {% if content is not null %}
                    <section id="page" class="{{ course_history_page }}">
                        {{ content }}
                    </section>
                {% endif %}
            {% endblock %}

            {% if announcements_block %}
                <article id="homepage-announcements">
                {{ announcements_block }}
                </article>
            {% endif %}

            {% if course_category_block %}
                <article id="homepage-course-category">
                    {{ course_category_block }}
                </article>
            {% endif %}

            {% include template ~ "/layout/hot_courses.tpl" %}

            {% if plugin_content_bottom %}
                <div id="plugin_content_bottom">
                    {{plugin_content_bottom}}
                </div>
            {% endif %}
        </div>
    </div>
    {% if plugin_main_bottom %}
        <div class="page-main-bottom" class="col-md-12">
            {{ plugin_main_bottom }}
        </div>
    {% endif %}
</div>
{% endblock %}
