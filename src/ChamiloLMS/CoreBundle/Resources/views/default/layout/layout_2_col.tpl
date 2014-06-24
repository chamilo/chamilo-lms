{% extends "@template_style/layout/main.tpl" %}

{% block body %}
	<div id="main_content" class="col-lg-10 col-sm-11">
        {% block right_column %}

            <section id="page_wrapper">

            {#  Plugin bottom  #}
            {% if plugin_content_top %}
                <div id="plugin_content_top">
                    {{ plugin_content_top }}
                </div>
            {% endif %}

            {#  Portal homepage  #}
            {% if home_page_block %}
                <div id="homepage">
                    <div class="row">
                        <div class="col-md-9">
                        {{ home_page_block }}
                        </div>
                    </div>
                </div>
            {% endif %}

            {#  ??  #}
            {{ sniff_notification }}

            {% include "@template_style/layout/page_body.tpl" %}

            {% if content is not null %}
                {{ content }}
            {% endif %}

            {% include "@template_style/layout/page_post_body.tpl" %}

            {#  Announcements  #}
            {% if announcements_block %}
                <div id="announcements">
                {{ announcements_block }}
                </div>
            {% endif %}

            {# Course categories (must be turned on in the admin settings) #}
            {% if course_category_block %}
                <div id="course_category">
                    <div class="row">
                        <div class="col-md-9">
                        {{ course_category_block }}
                        </div>
                    </div>
                </div>
            {% endif %}

            {#  Hot courses template  #}
            {% include "@template_style/layout/hot_courses.tpl" %}

            {#  Content bottom  #}
            {% if plugin_content_bottom %}
                <div id="plugin_content_bottom">
                    {{plugin_content_bottom}}
                </div>
            {% endif %}
            </section>
        {% endblock %}


        {# Footer #}
        {% include "@template_style/layout/footer.tpl" %}
	</div>
{% endblock %}
