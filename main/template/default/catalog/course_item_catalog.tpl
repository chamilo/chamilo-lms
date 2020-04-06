{% block course_item %}
    {% block course_image %}
        <div class="image">
            {% block course_thumbnail %}
                {% set class= '' %}
                {% if 'ajax' in course.image_url %}
                    {% set class= 'ajax' %}
                {% endif %}
                <a href="{{ course.image_url }}" title="{{ course.title }}" class="{{ class }}">
                    <img class="img-responsive" src="{{ course.thumbnail }}"  alt="{{ course.title }}"/>
                </a>
            {% endblock %}

            {% if course.category_title %}
                <span class="category">
                    <a href="{{ course.category_code_link }}">
                        {{ course.category_title }}
                    </a>
                </span>
                <div class="cribbon"></div>
            {% endif %}

            {% block course_description_button %}
                <div class="user-actions">
                    {{ course.description_button }}
                </div>
            {% endblock %}
        </div>
    {% endblock %}

    {% block course_description %}
        <div class="description">
            {% block course_title %}
                <div class="block-title">
                    <h4 class="title">
                        {% set class= '' %}
                        {% if 'ajax' in course.title_url %}
                            {% set class= 'ajax' %}
                        {% endif %}

                        <a title="{{ course.title }}" href="{{ course.title_url }}" class="{{ class }}">
                            {{ course.title }}
                        </a>
                        {% if course.admin_url %}
                            <a href="{{ course.admin_url }}">
                                {{ 'edit.png'|img(22, 'Edit'|get_lang) }}
                            </a>
                        {% endif %}
                    </h4>
                </div>
            {% endblock %}

            {% block course_rating %}
                {{ course.rating }}
            {% endblock %}

            {% block course_extras %}
                {% if course.extra_data %}
                    <div class="toolbar row">
                        <div class="col-sm-12">
                            {% for field in course.extra_data %}
                                {% if field.value_as_array %}
                                    <div class="panel-tags">
                                        <ul class="list-inline course-tags">
                                            <li> {{ field.text }} :</li>
                                            {% for tag  in field.value_as_array %}
                                                <li class="label label-info">
                                                    <span>
                                                        <a href="{{ catalog_url_no_extra_fields }}&extra_{{ field.variable }}%5B%5D={{ tag }}" >
                                                            {{ tag }}
                                                        </a>
                                                    </span>
                                                </li>
                                            {% endfor %}
                                        </ul>
                                    </div>
                                {% else %}
                                    {{ field.text }} : {{ field.value }}       <br />
                                {% endif %}

                            {% endfor %}

{#                            {% if course.extra_data_tags %}ss#}
{#                                {% for tags in course.extra_data_tags %}#}
{#                                    <div class="panel-tags">#}
{#                                        <ul class="list-inline course-tags">#}
{#                                            <li> {{ 'Tags' | get_lang }}</li>#}
{#                                            {% for tag in tags %}#}
{#                                                <li class="label label-info">#}
{#                                                    <span>#}
{#                                                        {{ tag }}#}
{#                                                    </span>#}
{#                                                </li>#}
{#                                            {% endfor %}#}
{#                                        </ul>#}
{#                                    </div>#}
{#                                {% endfor %}#}
{#                            {% endif %}#}
                        </div>
                    </div>
                {% endif %}
            {% endblock %}

            {% block course_teacher_info %}
                {{ course.teacher_info }}
            {% endblock  %}

            {% block course_buy_course %}
                {{ course.buy_course }}
            {% endblock %}

            {% block course_toolbar %}
                <div class="toolbar row">
                    {% if course.already_registered_formatted %}
                        <div class="col-sm-6">
                            {{ course.unregister_formatted }}
                        </div>
                        <div class="col-sm-6">
                            {{ course.already_registered_formatted }}
                        </div>
                    {% else %}
                        <div class="col-sm-12">
                            {{ course.subscribe_formatted }}
                        </div>
                    {% endif %}
                </div>
            {% endblock %}
        </div>
    {% endblock %}
{% endblock %}