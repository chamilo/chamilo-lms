{% for hot_course in hot_courses %}               
    {% if hot_course.extra_info.title %}        
        <div class="span5">
            <div class="well_border">
                <div class="row">
                    <div class="span2">
                        <div class="thumbnail">
                            <img src="{{ hot_course.extra_info.course_image }}" />                                    
                        </div>
                    </div>
                    <div class="span2">
                        <div class="categories-course-description">
                            <h3>{{ hot_course.extra_info.title}}</h3>
                            <h5>{{ hot_course.extra_info.teachers }}</h5>
                            {# hot_course.extra_info.rating_html #}
                        </div>
                        <p>                                
                            {{ hot_course.extra_info.go_to_course_button }}
                            {{ hot_course.extra_info.description_button }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    {% endif %}
{% endfor %}