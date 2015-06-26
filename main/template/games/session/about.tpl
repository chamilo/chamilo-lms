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
                <div class="block">
                    <div class="description-course">
                        <i class="fa fa-square"></i>
                        {{ course_data.description.getContent }}
                    </div>
                    <div class="time-course">
                        <i class="fa fa-clock-o"></i> <span>3 Horas</span>
                    </div>
                    <div class="level-course">
                        <i class="fa fa-star-o"></i> <span>Intermedio</span>
                    </div>
                    {% if course_data.tags %}
                        <div class="tags-course">
                            <i class="fa fa-check-square-o"></i>
                            {% for tag in course_data.tags %}
                                <a href="#">{{ tag.getTag }}</a>
                            {% endfor %}
                        </div>
                    {% endif %}
                    <div class="subscribe text-right">
                        <a href="#" class="btn btn-success">{{ "Subscribe"|get_lang }}</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row info-course">
            <div class="col-xs-12 col-md-12">
                <h4 class="title-section">{{ "CourseInformation"|get_lang }}</h4>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12 col-md-7">

                <div class="panel-body">
                    {% if course_data.objectives %}
                        <div class="objective-course">
                            <h4 class="title-info"><i class="fa fa-square"></i> {{ "Objectives"|get_lang }}</h4>
                            <div class="block">
                                <div class="content-info">
                                    {{ course_data.objectives.getContent }}
                                </div>
                            </div>
                        </div>
                    {% endif %}
                    {% if course_data.topics %}
                        <div class="topics">
                            <h4 class="title-info"><i class="fa fa-square"></i> {{ "Topics"|get_lang }}</h4>
                            <div class="block">
                                <div class="content-info">
                                    {{ course_data.topics.getContent }}
                                </div>
                            </div>
                        </div>
                    {% endif %}
                </div>

            </div>

            <div class="col-xs-12 col-md-5">
                <div class="block top">
                    {% if course_data.coaches %}
                        <div class="teachers">
                            <div class="heading">
                                <h4>{{ "Coaches"|get_lang }}</h4>
                            </div>
                            <div class="panel-body">
                                {% for coach in course_data.coaches %}
                                    <div class="teachers-dates">
                                        <div class="row">
                                            <div class="col-xs-7 col-md-7">
                                                <h4><i class="fa fa-circle"></i> {{ coach.complete_name }}</h4>
                                                {% for extra_field in coach.extra_fields %}
                                                    <div class="extras-field">{{ extra_field.value }}</div>
                                                {% endfor %}
                                            </div>
                                            <div class="col-xs-5 col-md-5">
                                                <div class="text-center">
                                                    <img class="img-circle" src="{{ coach.image }}" alt="{{ coach.complete_name }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                {% endfor %}
                            </div>
                        </div>
                    {% endif %}
                    <div class="social-share">
                        <div class="heading"><h4>¡{{ "ShareWithYourFriends"|get_lang }}!</h4></div>
                        <div class="panel-body">
                            <div class="icons-social text-center">
                                <a href="https://www.facebook.com/sharer/sharer.php?{{ {'u': pageUrl}|url_encode }}" target="_blank"  class="btn-social">
                                    <i class="fa fa-facebook"></i>
                                </a>
                                <a href="https://twitter.com/home?{{ {'status': session.getName() ~ ' ' ~ pageUrl}|url_encode }}" target="_blank" class="btn-social">
                                    <i class="fa fa-twitter"></i>
                                </a>
                                <a href="https://www.linkedin.com/shareArticle?{{ {'mini': 'true', 'url': pageUrl, 'title': session.getName() }|url_encode }}" target="_blank" class="btn-social">
                                    <i class="fa fa-linkedin"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="subscribe text-center">
                    <a href="#" class="btn btn-success btn-lg">{{ "Subscribe"|get_lang }}</a>
                </div>
            </div>
        </div>
    {% endfor %}
</div>