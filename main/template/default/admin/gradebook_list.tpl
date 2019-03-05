{% extends 'layout/layout_1_col.tpl'|get_template %}

{% block content %}
    {{ form }}

    {% if gradebook_list %}
        <table class="table">
            <thead class="title">
                <tr>
                    <th>{{ 'Name' | get_lang }}</th>
                    <th>{{ 'Course' | get_lang }}</th>
                    <th>{{ 'Actions' | get_lang }} </th>
                </tr>
            </thead>
        {% for item in gradebook_list %}
            <tr>
                <td>
                    {{ item.name | e }}
                </td>
                <td>
                    {{ item.courseCode }}
                </td>
                <td>
                    <a href="{{ current_url }}&action=edit&id={{ item.id }}">
                        <img src="{{ 'edit.png'|icon(22) }}" />
                    </a>

                    <a href="{{ _p.web_main }}admin/gradebook_dependency.php?id={{ item.id }}">
                        <img src="{{ '2rightarrow.png'|icon(22) }}" />
                    </a>
                </td>
            </tr>
        {% endfor %}
        </table>

       {{ gradebook_list }}
    {% endif %}
{% endblock %}
