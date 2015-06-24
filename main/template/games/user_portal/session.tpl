{% if not session.show_simple_session_info %}
    <div class="title-course">
        {% if session.show_link_to_session %}
            <a href="{{ _p.web_main ~ 'session/index.php?session_id=' ~ session.id }}" alt="{{ session.title }}" title="{{ session.title }}">
                <i class="fa fa-square"></i> {{ session.title }}
            </a>
        {% else %}
            <i class="fa fa-square"></i> {{ session.title }}
        {% endif %}
        {% if session.show_actions %}
            <div class="pull-right">
                <a href="{{ _p.web_main ~ "session/resume_session.php?id_session=" ~ session.id }}">
                    <img src="{{ "edit.png"|icon(22) }}" alt="{{ "Edit"|get_lang }}" title="{{ "Edit"|get_lang }}">
                </a>
            </div>
        {% endif %}
    </div>
{% endif %}

<div class="current-item">
    {% if session.show_simple_session_info %}

    {% else %}
        <div class="row">
            <div class="col-md-4">
                <div class="embed-responsive embed-responsive-16by9">
                    <img src="{{ session.image }}">
                </div>
            </div>
            <div class="col-md-8">
                {% for course in session.courses %}
                    <div>
                        {{ session.courses|length > 1 ? course.title }}

                        {% if course.description %}
                            <div class="description-course">
                                {{ course.description }}
                            </div>
                        {% endif %}

                        {% if course.coaches %}
                            <div class="teachers-course">
                                <i class="fa fa-pencil-square"></i> {{ course.coaches }}
                            </div>
                        {% endif %}

                        {% if course.human.text.duration %}
                            <div class="time-course">
                                <i class="fa fa-clock-o"></i>
                                <span class="text-uppercase"> {{ course.human.text.duration }} </span>
                            </div>
                        {% endif %}

                        {% if session.courses|length > 1 %}
                            <div class="row">
                                <div class="col-md-offset-6 col-md-6">
                                    <a class="btn btn-press" href="{{ course.link }}" role="button">{{ 'Continue'|get_lang }}</a>
                                </div>
                            </div>
                        {% endif %}
                    </div>
                {% endfor %}

                <div class="row">
                    <div class="col-md-6">
                        {% if gamification_mode %}
                            <div class="row">
                                <div class="col-xs-7">
                                    <div class="start-progress">
                                        {% if session.stars > 0%}
                                            {% for i in 1..session.stars %}
                                                <i class="fa fa-star fa-2x"></i>
                                            {% endfor %}
                                        {% endif %}
                                        {% if session.stars < 4 %}
                                            {% for i in 1..4 - session.stars %}
                                                <i class="fa fa-star-o fa-2x"></i>
                                            {% endfor %}
                                        {% endif %}
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12">
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: {{ session.progress }}%;">
                                            <span class="sr-only">{{ session.progress }}% {{ 'Completed'|get_lang }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">{{ session.progress }}%</div>
                                </div>
                            </div>
                        {% endif %}
                    </div>

                    {% if session.courses|length == 1 %}
                        {% set course = session.courses|first %}
                        <div class="col-md-6">
                            <a class="btn btn-press" href="{{ course.link }}" role="button">{{ 'Continue'|get_lang }}</a>
                        </div>
                    {% endif %}
                </div>
            </div>
        </div>
    {% endif %}
</div>
