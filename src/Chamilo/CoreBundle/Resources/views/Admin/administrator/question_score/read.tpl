{% extends "@template_style/layout/layout_1_col.tpl" %}
{% block content %}
    {{ item.id }} {{ item.name  }}
    <hr />
    <ul>
    {% for subitem in subitems %}
        <li>
            <a href="{{ url(links.question_score_name_read_link, { id: subitem.id} ) }}">{{ subitem.name }}</a>
        </li>
    {% endfor %}
    </ul>

{% endblock %}
