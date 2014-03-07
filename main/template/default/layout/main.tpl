{% block header %}
{% include app.template_style ~ "/layout/main_header.tpl" %}
{% endblock %}

{% block body %}
    {% block content %}
    {% endblock %}
{% endblock %}

{% block footer %}
    {#  Footer  #}
    {% if show_footer == true %}
        </div> <!-- end of row -->
    </div> <!-- end of main -->
    {% endif %}
    {% if show_footer == true %}
        {% include app.template_style ~ "/layout/footer.tpl" %}
    {% endif %}
{{ xhprof }}
{% endblock %}
