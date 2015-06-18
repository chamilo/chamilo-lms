<div id="about-session">
{% for course_data in courses %}
    {% if courses|length > 1 %}
 <div class="row">
    <div class="col-xs-12">
        <h2 class="text-uppercase">{{ course_data.course.getTitle }}</h2>
    </div>
 </div>
    {% endif %}

    <div class="row">
        {% if course_data.video %}
            <div class="col-sm-6 col-md-7">
                <div class="embed-responsive embed-responsive-16by9">
                    {{ course_data.video }}
                </div>
            </div>
        {% endif %}

        <div class="{{ course_data.video ? 'col-sm-6 col-md-5' : 'col-sm-12' }}">
            <div class="description-course">
                {{ course_data.description.getContent }}
            </div>
            {% if course_data.tags %}
                <div class="tags-course">
                    <i class="fa fa-check-square-o"></i>
                       {% for tag in course_data.tags %}
                       <span>{{ tag.getTag }}</span>
                       {% endfor %}
                </div>
            {% endif %}
                <div class="subscribe">
                    <a href="#" class="btn btn-success btn-lg">{{ "Subscribe"|get_lang }}</a>
                </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-md-12">
            <h3>{{ "CourseInformation"|get_lang }}</h3>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-md-7">
            {% if course_data.objectives %}
                <div class="objective-course">
                    <h4>{{ "Objectives"|get_lang }}</h4>
                        {{ course_data.objectives.getContent }}
                </div>
            {% endif %}
            {% if course_data.topics %}
                <div class="topics">
                    <h4>{{ "Topics"|get_lang }}</h4>
                    {{ course_data.topics.getContent }}
                </div>
            {% endif %}
        </div>

        <div class="col-xs-12 col-md-5">
            {% if course_data.coaches %}
            <div class="coaches">
                <h4>{{ "Coaches"|get_lang }}</h4>
                {% for coach in course_data.coaches %}
                    <div class="row">
                        <div class="col-md-6">
                            <h4>{{ coach.complete_name }}</h4>
                            {% if coach.officer_position %}
                            <p>{{ coach.officer_position }}</p>
                            {% endif %}

                            {% if coach.work_or_study_place %}
                            <p>{{ coach.work_or_study_place }}</p>
                            {% endif %}
                        </div>
                        <div class="col-md-6">
                            <img src="{{ coach.image }}" alt="{{ coach.complete_name }}">
                        </div>
                    </div>
                {% endfor %}
            </div>
            {% endif %}
            <div class="social-share">
                <div class="text-center">{{ "ShareWithYourFriends"|get_lang }}</div>
                <div class="icons-social text-center">
                    <a href="#" class="btn bnt-link btn-lg">
                        <i class="fa fa-facebook fa-2x"></i>
                    </a>
                    <a href="#" class="btn bnt-link btn-lg">
                        <i class="fa fa-twitter fa-2x"></i>
                    </a>
                    <a href="#" class="btn bnt-link btn-lg">
                        <i class="fa fa-linkedin fa-2x"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-md-12">
            <div class="text-center">
                <a href="#" class="btn btn-success btn-lg">{{ "Subscribe"|get_lang }}</a>
            </div>
        </div>
    </div>
{% endfor %}
</div>