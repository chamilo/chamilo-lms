{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    {{ 'Results' | trans }}
    <hr />
    {% if pagination != '' %}
        <ul>
            {% for user in pagination.currentPageResults %}
                <li>
                    {{ user.firstname }} - {{ user.lastname }} {{ user.score }}
                    <a class="btn" href="{{ url('curriculum_user.controller:getUserItemsAction',
                    { 'userId': user.userId, 'course' : course.code, 'id_session' : course_session.id }) }}">
                        {{ 'Details' | trans }}
                    </a>
                </li>
            {% endfor %}
        </ul>

        {{ pagerfanta(pagination, 'twitter_bootstrap', { 'proximity': 3 } ) }}

    {% endif %}

{% endblock %}
