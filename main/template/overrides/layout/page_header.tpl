<div id="navigation" class="notification-panel">
    {{ help_content }}
    {{ bug_notification }}
</div>
{% block topbar %}
{% include 'layout/topbar.tpl'|get_template %}
{% endblock %}

{% if header_extra_content is not null %}
{{ header_extra_content }}
{% endif %}

{% block menu %}
{% include 'layout/menu.tpl'|get_template %}
{% endblock %}

{% include 'layout/course_navigation.tpl'|get_template %}
