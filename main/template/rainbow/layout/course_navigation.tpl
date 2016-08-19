{# Course navigation icons - these are two optional features that have to be enabled through admin settings #}
{% if show_header == true %}
    {% if show_course_navigation_menu is not null %}
        <div class="nav-tools">
            {{ show_course_navigation_menu }}
        </div>
    {% endif %}
{% endif %}
