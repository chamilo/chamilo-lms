{% extends template ~ "/layout/main.tpl" %}

{% block body %}
	{# Main content #}

    {# Plugin main top #}
    {% if plugin_main_top %}
        <div id="plugin_main_top" class="col-md-12">
            {{ plugin_main_top }}
        </div>
    {% endif %}


        
        {% if home_page_block %}
            <!-- Bloque de contenido home -->
            <div class="my-home">
               {{ home_page_block }}
            </div>
        {% endif %}
        


        {# Homepage bottom region #}
        {% if is_homepage and plugin_homepage_bottom %}
        <div id="plugin-homepage" class="col-xs-12">
            {{ plugin_homepage_bottom }}
        </div>
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
            <section id="page-content" class="{{ course_history_page }}">
                {% if template == 'games' %}
                    <ul class="games nav nav-tabs" role="tablist">
                        <li role="presentation" {% if not history %} class="active" {% endif %}>
                            <a href="{{ _p.web }}user_portal.php?nosession=true" role="tab">Cursos Actuales</a>
                        </li>
                        <li role="presentation" {% if history %} class="active" {% endif %}>
                            <a href="{{ _p.web }}user_portal.php?history=1" role="tab">Cursos Concluidos</a>
                        </li>
                    </ul>
                {% endif %}

                <div class="block-course">
                    {{ content }}
                </div>
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
