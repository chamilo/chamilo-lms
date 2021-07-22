{% for item in hot_courses %}
    {% if item.title %}
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="items items-hotcourse">
                <div class="image">
                    {% set title %}
                    <a title="{{ item.title}}" href="{{ _p.web }}course/{{ item.real_id  }}/about">
                        <img src="{{ item.course_image_large }}" class="img-responsive" alt="{{ item.title }}">
                    </a>
                    {% endset %}
                    {{ title | remove_xss }}

                    {% if item.categoryName != '' %}
                        <span class="category">{{ item.categoryName }}</span>
                        <div class="cribbon"></div>
                    {% endif %}
                    <div class="user-actions">{{ item.description_button }}</div>
                </div>
                <div class="description">
                    <div class="block-title">
                        {% set title %}
                            <h5 class="title">
                                {% if item.is_course_student or item.is_course_teacher %}
                                    <a alt="{{ item.title }}" title="{{ item.title }}" href="{{ _p.web }}courses/{{ item.directory  }}/">
                                        {{ item.title_cut}}
                                    </a>
                                {% else %}
                                    <a alt="{{ item.title }}" title="{{ item.title }}" href="{{ _p.web }}course/{{ item.real_id  }}/about">
                                        {{ item.title_cut}}
                                    </a>
                                {% endif %}
                            </h5>
                        {% endset %}
                        {{ title | remove_xss }}
                    </div>
                    <div class="ranking">
                        {{ item.rating_html }}
                    </div>
                    <div class="toolbar row">
                        <div class="col-sm-4">
                            {% if item.price %}
                                {{ item.price }}
                            {% endif %}
                        </div>
                        <div class="col-sm-8">
                            <div class="btn-group" role="group">
                                {{ item.register_button }}
                                {{ item.unsubscribe_button }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {% endif %}
{% endfor %}
