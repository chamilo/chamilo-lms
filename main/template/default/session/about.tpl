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
            <div class="well">
                {{ course_data.description.getContent }}

                <p class="text-right text-uppercase">
                    <a href="#" class="btn btn-success">{{ "Subscribe"|get_lang }}</a>
                </p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12">
            <h3 class="text-uppercase">{{ "CourseInformation"|get_lang }}</h3>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6 col-md-7">
            {% if course_data.objectives %}
                <div class="row">
                    <div class="col-xs-12">
                        <h4>{{ "Objectives"|get_lang }}</h4>
                        <div class="well">
                            {{ course_data.objectives.getContent }}
                        </div>
                    </div>
                </div>
            {% endif %}

            {% if course_data.topics %}
                <div class="row">
                    <div class="col-xs-12">
                        <h4>{{ "Topics"|get_lang }}</h4>
                        <div class="well">
                            {{ course_data.topics.getContent }}
                        </div>
                    </div>
                </div>
            {% endif %}
        </div>

        <div class="col-sm-6 col-md-5">
            <div class="row">
                <div class="col-xs-12">
                    <div class="well">
                        {% if course_data.coaches %}
                            <h5>{{ "Coaches"|get_lang }}</h5>

                            <ul>
                                {% for coach in course_data.coaches %}
                                    <li>
                                        {{ coach.getCompleteName }}
                                    </li>
                                {% endfor %}
                            </ul>
                            <hr>
                        {% endif %}

                        <p class="text-center">{{ "ShareWithYourFriends"|get_lang }}</p>
                        <p class="text-center">
                            <a href="#" class="btn bnt-link btn-lg">
                                <i class="fa fa-facebook fa-2x"></i>
                            </a>
                            <a href="#" class="btn bnt-link btn-lg">
                                <i class="fa fa-twitter fa-2x"></i>
                            </a>
                            <a href="#" class="btn bnt-link btn-lg">
                                <i class="fa fa-linkedin fa-2x"></i>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12">
            <p class="text-center text-uppercase">
                <a href="#" class="btn btn-success">{{ "Subscribe"|get_lang }}</a>
            </p>
        </div>
    </div>
{% endfor %}
