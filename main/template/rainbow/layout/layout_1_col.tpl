{% extends template ~ "/layout/main.tpl" %}
{% block body %}
    <div class="container{{ fluid == true ? '-fluid':'' }}">
        {% include template ~ "/layout/page_body.tpl" %}
        {% block content %}
            {% if content is not null %}
                <section id="main_content" class="row">
                    <div class="col-md-12">
                        {{ content }}
                    </div>
                </section>
            {% endif %}
        {% endblock %}
        &nbsp;
    </div>
{% endblock %}
