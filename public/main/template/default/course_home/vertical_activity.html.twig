{% for block in blocks %}
    <div class="{{ block.class ?: 'courseadminview-activity-3col' }}">
        {% if block.title %}
            <span class="viewcaption">{{ block.title }}</span>
        {% endif %}

        {% for item in block.content %}
            {% if loop.index0 == 0 %}
                <ul>
            {% endif %}

            <li class="course-tool">
                {{ item.extra }}
                {{ item.visibility }}

                {% set a_params = '' %}

                {% for foo, bar in item.url_params %}
                    {% set a_params = a_params ~ [foo, bar]|join('="') ~ '" ' %}
                {% endfor %}

                <a {{ a_params }} >
                    <img src="{{ item.tool.image|replace({'.gif': '.png'})|icon() }}" alt="{{ item.name }}"
                         id="toolimage_{{ item.tool.iid }}">
                </a>
                {{ item.link }}
            </li>

            {% if block.content|length == loop.index %}
                </ul>
            {% endif %}
        {% endfor %}
    </div>
{% endfor %}
