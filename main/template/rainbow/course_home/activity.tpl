{% for block in blocks %}
    {% if block.title %}
        <h2 class="title-tools">{{ block.title }}</h2>
    {% endif %}

    <div class="row {{ block.class }}">
        {% if 'homepage_view'|api_get_setting == 'activity' %}
            {% for item in block.content %}
                <div class="offset2 col-md-4 course-tool">
                    {{ item.extra }}
                    {{ item.visibility }}
                    {{ item.icon }}
                    {{ item.link }}
                </div>
            {% endfor %}
        {% endif %}

        {% if 'homepage_view'|api_get_setting == 'activity_big' %}
            {% for item in block.content %}
                <div class="col-xs-6 col-md-3 course-tool">
                    <div class="big_icon">
                        {{ item.tool.image }}
                    </div>
                    <div class="content">
                        <h4>
                            {{ item.visibility }}
                            {{ item.extra }}
                            {{ item.link }}
                        </h4>
                    </div>
                </div>
            {% endfor %}
        {% endif %}
    </div>
{% endfor %}
