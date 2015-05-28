{% extends template ~ "/layout/main.tpl" %}

{% block body %}
	{# Main content #}

    {# Plugin main top #}
    {% if plugin_main_top %}
        <div id="plugin_main_top" class="col-md-12">
            {{ plugin_main_top }}
        </div>
    {% endif %}

        <!-- Inicia el slider -->
            {# Announcements  #}
            {% if announcements_block %}
                <div class="slider-top">
                    {{ announcements_block }}
                </div>
            {% endif %}
        <!-- fin del slider -->
        {% if _u.logged == 0 %}
            {% if home_page_block %}
            <!-- Bloque de contenido home -->
            <div class="my-home">
                    {{ home_page_block }}
            </div>
            {% endif %}
        {% endif %}
        {% if plugin_content_top %}
        <div id="plugin_content_top">
            {{ plugin_content_top }}
        </div>
        {% endif %}
        {% if plugin_content_bottom %}
        <div id="plugin_content_bottom">
            {{plugin_content_bottom}}
        </div>
        {% endif %}

	    {#  Right column #}
	    <div class="col-md-12 menu-column">
        {% if plugin_menu_top %}
            <div id="plugin_menu_top">
                {{plugin_menu_top}}
            </div>
        {% endif %}



	    <div class="row">
            <div class="col-md-4">
                {# User Profile links #}
                {{ profile_block }}
            </div>
            <div class="col-md-4">
                {# Course block - admin #}
                {{ course_block }}
            </div>
            <div class="col-md-4">
                {# Skills #}
                {{ skills_block }}
            </div>
	    </div>


		{# Plugin courses sidebar #}
        {# Plugins for footer section #}

        {% if plugin_menu_bottom %}
            <div id="plugin_menu_bottom">
                {{ plugin_menu_bottom }}
            </div>
        {% endif %}
	</div>

	<div class="col-md-12">
        {# Plugin bottom #}



		{#  ??  #}
		{{ sniff_notification }}

        {% block page_body %}
        {% include template ~ "/layout/page_body.tpl" %}
        {% endblock %}

        {# Welcome to course block  #}
        {% if welcome_to_course_block %}
            <section id="homepage-course">
            {{ welcome_to_course_block }}
            </section>
        {% endif %}

        {% block content %}
        {% if content is not null %}
            <section id="page-content">
                {{ content }}
            </section>
        {% endif %}
        {% endblock %}



        {# Course categories (must be turned on in the admin settings) #}
        {% if course_category_block %}
            <section id="homepage-course-category">
                {{ course_category_block }}
            </section>
        {% endif %}

		{# Hot courses template  #}
		{% include template ~ "/layout/hot_courses.tpl" %}



        &nbsp;
	</div>

    {# Plugin main bottom #}
    {% if plugin_main_bottom %}
        <div id="plugin_main_bottom" class="col-md-12">
            {{ plugin_main_bottom }}
        </div>
    {% endif %}

{% endblock %}
