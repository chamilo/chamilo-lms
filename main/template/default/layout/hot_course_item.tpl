{% for hot_course in hot_courses %}
    {% if hot_course.extra_info.title %}
                    <div class="col-md-4">
                        <div class="items-course">
                            <div class="items-course-image">
                                <a href="{{ hot_course.extra_info.course_public_url }}"><img class="img-responsive" src="{{ hot_course.extra_info.course_image_large }}" alt="{{ hot_course.extra_info.title|e }}"/></a>
                            </div>
                            <div class="items-course-info">
                                <h4 class="title">
                                    <a title="{{ hot_course.extra_info.title}}" href="{{ hot_course.extra_info.course_public_url }}">{{ hot_course.extra_info.title}}</a>
                                </h4>
                                <div class="teachers">{{ hot_course.extra_info.teachers }}</div>
                                <div class="ranking">
                                    {{ hot_course.extra_info.rating_html }}
                                </div>
                                <div class="toolbar">
                                    <div class="btn-group" role="group">
                                    {{ hot_course.extra_info.description_button }}
                                    {{ hot_course.extra_info.register_button }}
                                    {{ hot_course.extra_info.unsubscribe_button }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
    {% endif %}
{% endfor %}
