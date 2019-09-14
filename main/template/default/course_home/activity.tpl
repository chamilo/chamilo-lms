{% for block in blocks %}
    {% if block.title %}
        <div class="page-header">
            <h4 class="title-tools">{{ block.title }}</h4>
        </div>
    {% endif %}

    <div class="row {{ block.class }}">
        {% if 'homepage_view'|api_get_setting == 'activity' %}
            {% for item in block.content %}
                <div class="offset2 col-md-4 course-tool">
                    {{ item.extra }}
                    {{ item.visibility }}
                    {{ item.only_icon_small }}
                    {{ item.link }}
                </div>
            {% endfor %}
        {% endif %}

        {% if 'homepage_view'|api_get_setting == 'activity_big' %}
            {% for item in block.content %}
                <div class="col-xs-6 col-sm-4 col-md-3">
                    <div class="course-tool">
                        <div class="big_icon">
                            {{ item.tool.image }}
                        </div>
                        <div class="content">
                            {{ item.visibility }}
                            {{ item.extra }}
                            {{ item.link }}
                        </div>
                    </div>
                </div>
            {% endfor %}
        {% endif %}
    </div>

{% endfor %}
