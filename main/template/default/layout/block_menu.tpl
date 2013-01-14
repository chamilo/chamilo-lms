{% if block_menu is not empty %}
<div id="{{ block_menu.id }}" class="well sidebar-nav">
    {% block block_menu_item %}
        {% if block_menu.title is not empty %}
            <h4>{{ block_menu.title }}</h4>
        {% endif %}

        {% if block_menu.elements is not empty %}
        <ul class="nav nav-list">
            {% for item in block_menu.elements %}
                <li><a href="{{ item.href }}"> {{ item.title }}</a></li>
            {% endfor %}
        </ul>
        {% endif %}
        {{ block_menu.content }}
    {% endblock %}
</div>
{% endif %}