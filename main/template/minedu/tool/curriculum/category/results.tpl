{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    {{ 'Results' | trans }}
    <hr />
    {% if pagination != '' %}
        <table class="data_table">
            <tr>
                <th>
                    {{ 'User' | trans }}
                </th>
                <th>
                    {{ 'Score' | trans }}
                </th>
                <th>
                    {{ 'Actions' | trans }}
                </th>
            </tr>
            {% for user in pagination.currentPageResults %}
                <tr>
                    <td>
                        {{ user.firstname }} {{ user.lastname }}
                    </td>
                    <td>
                        <div class="label label-success"> {{ user.score }}</div>
                    </td>

                    <td>
                        <a class="btn" href="{{ url('curriculum_user.controller:getUserItemsAction',
                        { 'userId': user.userId, 'course' : course.code, 'id_session' : course_session.id }) }}">
                            {{ 'Details' | trans }}
                        </a>
                    </td>
                </tr>
            {% endfor %}
        </table>

        {{ pagerfanta(pagination, 'twitter_bootstrap', { 'proximity': 3 } ) }}

    {% endif %}

{% endblock %}
