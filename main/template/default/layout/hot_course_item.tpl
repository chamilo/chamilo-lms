{% for hot_course in hot_courses %}               
    {% if hot_course.extra_info.title %}        
        <div class="span9 hot-course-box">
            <div class="well_border">
                <div class="row">
                    <div class="span2">
                        <div class="thumbnail">
                            <img src="{{ hot_course.extra_info.course_image }}" alt="" />
                        </div>
                    </div>
                    <div class="span6">
                        <div class="categories-course-description">
                            <div class="text-h3">{{ hot_course.extra_info.title}}</div>
                            <div class="text-h5">{{ hot_course.extra_info.teachers }}</div>
                            {{ hot_course.extra_info.rating_html }}
                        </div>
                        <p>                                                            
                            {{ hot_course.extra_info.description_button }}
                            {{ hot_course.extra_info.go_to_course_button }}
                            {{ hot_course.extra_info.register_button }}
                            {{ hot_course.extra_info.unsubscribe_button }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    {% endif %}
{% endfor %}
