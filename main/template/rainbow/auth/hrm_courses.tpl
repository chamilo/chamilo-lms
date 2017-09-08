{% for user in users %}
    <div class="panel panel-info">
        <div class="panel-heading">
            <h3 class="panel-title">
                <img src="{{ user.picture_url }}" alt="{{ user.complete_name }}">
                {{ user.complete_name }}
                <small>{{ user.username }}</small>
            </h3>
        </div>
        <div class="panel-body">
            {% if not user.course_list %}
                <div class="alert alert-warning">{{ 'UserHasNoCourse'|get_lang }}</div>
            {% else %}
                {{ user.course_list }}
            {% endif %}
        </div>
    </div>
{% endfor %}
