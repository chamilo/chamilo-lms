{% if not session.show_simple_session_info %}
    <div class="title-course">
        {% if session.show_link_to_session %}
            {% if session.courses|length == 1 %}
                {% set course = session.courses|first %}
                <a href="{{ course.link }}" alt="{{ session.title }}" title="{{ session.title }}">
                    <i class="fa fa-square"></i> {{ session.title }}
                </a>
            {% else %}
                <a href="{{ _p.web_main ~ 'session/index.php?session_id=' ~ session.id }}" alt="{{ session.title }}" title="{{ session.title }}">
                    <i class="fa fa-square"></i> {{ session.title }}
                </a>
            {% endif %}
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
                {% for field_value in session.extra_fields %}
                    {% if field_value.field.variable == 'image' %}
                        <div class="thumbnail">
                            <img src="{{ _p.web_upload ~ field_value.value }}" class="media-gris">
                            <div class="trophy"><img src="{{ _p.web_css_theme }}images/trophy.png"></div>
                        </div>

                    {% endif %}
                {% endfor %}
                {% if session.courses|length > 1 %}

                {% for field_value in session.extra_fields %}
                {% if field_value.field.variable == 'human_text_duration' %}
                <div class="time-course">
                    <i class="fa fa-clock-o"></i>
                    <span class="text-uppercase"> {{ field_value.value }} </span>
                </div>
                {% endif %}
                {% endfor %}

                {% if gamification_mode %}
                <div class="progress-session">
                    <div class="row">
                        <div class="col-xs-7">
                            <div class="start-progress">
                                {% if session.stars > 0%}
                                {% for i in 1..session.stars %}
                                <i class="fa fa-star"></i>
                                {% endfor %}
                                {% endif %}
                                {% if session.stars < 4 %}
                                {% for i in 1..4 - session.stars %}
                                <i class="fa fa-star plomo"></i>
                                {% endfor %}
                                {% endif %}
                            </div>
                        </div>
                        <div class="col-xs-5 text-right">
                            <span class="score">{{ 'XPoints'|get_lang|format(session.points) }}</span>
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
                </div>
                {% endif %}
                {% endif %}
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
                                {% if course.coaches|length > 0 %}
                                    <i class="fa fa-pencil-square"></i>

                                    {% for coach in course.coaches %}
                                        <a href="{{ _p.web_ajax ~ 'user_manager.ajax.php?' ~ {'a': 'get_user_popup', 'user_id': coach.user_id}|url_encode() }}" data-title="{{ coach.full_name }}" class="ajax">
                                            <span><i class="fa fa-square"></i> {{ coach.full_name }}</span>
                                        </a>
                                    {% endfor %}
                                {% endif %}
                            </div>
                        {% endif %}

                        {% if session.courses|length > 1 %}
                            <div class="row">
                                <div class="col-md-12 text-right">
                                    <a class="btn btn-press" href="{{ course.link }}" role="button">{{ 'Continue'|get_lang }}</a>
                                </div>
                            </div>
                        {% endif %}
                    </div>
                {% endfor %}

                <div class="row">
                    <div class="col-md-6">
                        {% if session.courses|length == 1 %}
                        {% for field_value in session.extra_fields %}
                            {% if field_value.field.variable == 'human_text_duration' %}
                                        <div class="time-course">
                                            <i class="fa fa-clock-o"></i>
                                            <span class="text-uppercase"> {{ field_value.value }} </span>
                                        </div>
                            {% endif %}
                        {% endfor %}

                        {% if gamification_mode %}
                        <div class="progress-session">
                            <div class="row">
                                <div class="col-xs-7">
                                    <div class="start-progress">
                                        {% if session.stars > 0%}
                                            {% for i in 1..session.stars %}
                                                <i class="fa fa-star"></i>
                                            {% endfor %}
                                        {% endif %}
                                        {% if session.stars < 4 %}
                                            {% for i in 1..4 - session.stars %}
                                                <i class="fa fa-star plomo"></i>
                                            {% endfor %}
                                        {% endif %}
                                    </div>
                                </div>
                                <div class="col-xs-5 text-right">
                                    <span class="score">{{ 'XPoints'|get_lang|format(session.points) }}</span>
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
                        </div>
                        {% endif %}
                        {% endif %}
                    </div>

                    {% if session.courses|length == 1 %}
                        {% set course = session.courses|first %}
                        <div class="col-md-6 text-right">
                            <a class="btn btn-press" href="{{ course.link }}" role="button">{{ 'Continue'|get_lang }}</a>
                        </div>
                    {% endif %}
                </div>
            </div>
        </div>
    {% endif %}
</div>
