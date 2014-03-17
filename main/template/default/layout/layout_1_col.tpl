{% extends app.template_style ~ "/layout/main.tpl" %}

{#  1 column  #}
{% block body %}
    {#  Plugin top  #}
    {% if plugin_content_top %}
        <div id="plugin_content_top" class="col-lg-10 col-sm-11">
            {{ plugin_content_top}}
        </div>
    {% endif %}

    <div id="main_content" class="col-lg-10 col-sm-11">
        {# Breadcrumb #}
        {% include app.template_style ~ "/layout/breadcrumb.tpl" %}

        <div class="row">
            <div class="col-sm-12 col-md-12">
                <section id="page_wrapper">

                {% include app.template_style ~ "/layout/page_body.tpl" %}

                {% block content %}
                    {% if content is not null %}
                        {{ content }}
                    {% endif %}
                {% endblock %}

                {#  Plugin bottom  #}
                {% if plugin_content_bottom %}
                    <div id="plugin_content_bottom" class="col-md-12">
                        {{ plugin_content_bottom }}
                    </div>
                {% endif %}
                </section>

                {# Footer #}
                {% include app.template_style ~ "/layout/footer.tpl" %}
            </div>
        </div>
    </div>

    </body>
    </html>
{% endblock %}


