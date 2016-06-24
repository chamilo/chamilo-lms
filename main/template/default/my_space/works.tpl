{{ form }}
{% if session %}
    <h3 class="page-header">{{ session.name }}</h3>
    {% for course in courses %}
        <h4>{{ course.title }}</h4>
        {{ course.detail_table }}
    {% endfor %}
{% endif %}
