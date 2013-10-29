{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    {% if form %}
    <h3>Consultar mis resultados</h3>

    <form
        action = "{{ url('resultsAction') }}"
        method = "post" {{ form_enctype(form) }}>
    {{ form_widget(form) }}
    </form>
    {% else %}
        <h3>Resultados de DNI: {{ dni }}</h3>
        <table class="data_table">
        {% for category in category_list %}
            <tr>
                <td>
                {{ category.title }}
                </td>
                <td>
                    {{ category.score }}  / {{ category.total }}

                    {% if add_score[category.category_id] %}
                        (Modifier {{ add_score[category.category_id] }})
                    {% endif %}
                </td>
            <tr>
        {% endfor %}
        </table>

    {% endif %}
{% endblock %}