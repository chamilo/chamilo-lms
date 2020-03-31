{% block course_item %}
    {% block course_image %}
        <div class="image">
            {% block course_thumbnail %}
                <a href="{{ course.course_public_url }}" title="{{ course.title }}">
                    <img class="img-responsive" src="{{ course.thumbnail }}"  alt="{{ course.title }}"/>
                </a>
            {% endblock %}

            {% if course.category_title %}
                <span class="category">{{ course.category_title }}</span>
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
                {{ course.title_formatted }}
            {% endblock %}

            {% block course_rating %}
                {{ course.rating }}
            {% endblock %}

            {% block course_extras %}
                {% if course.extra_data %}
                    <div class="toolbar row">
                        <div class="col-sm-12">
                            {{ course.extra_data }}

                            {% if course.extra_data_tags %}
                                <div class="panel-tags">
                                    <ul class="list-inline course-tags">
                                        <li> {{ 'Tags' | get_lang }}</li>
                                        {% for tag in course.extra_data_tags %}
                                            <li class="label label-info">
                                                <span>{{ tag }}</span>
                                            </li>
                                        {% endfor %}
                                    </ul>
                                </div>
                            {% endif %}
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