{% for hot_course in hot_courses %}               
    {% if hot_course.extra_info.title %}        


                <div class="row">
                    <div class="col-md-2">
                        <div class="thumbnail">
                            <img src="{{ hot_course.extra_info.course_image }}" alt="{{ hot_course.extra_info.title|e }}" width="85" height="85" />
                        </div>
                    </div>
                    <div class="col-md-10">
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


    {% endif %}
{% endfor %}
