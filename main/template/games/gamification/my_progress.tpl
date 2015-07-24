<div class="my-progress row">
    <div class="col-md-4">
        <div class="profile-user">
            {{ user_avatar }}
            <div class="username">{{ user.getCompleteName() }}</div>
            <div class="gamification">
                <div class="row">
                    <div class="col-md-7">
                        <div class="star-progress">
                            {% if gamification_stars > 0 %}
                                {% for i in 1..gamification_stars %}
                                    <i class="fa fa-star"></i>
                                {% endfor %}
                            {% endif %}

                            {% if 4 - gamification_stars > 0 %}
                                {% for i in 1..(4 - gamification_stars) %}
                                    <i class="fa fa-star in"></i>
                                {% endfor %}
                            {% endif %}
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="bar-points">{{ 'XPoints'|get_lang|format(gamification_points) }}</div>
                    </div>
                </div>
                <div class="progress">
                    <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="{{ gamification_progress }}" aria-valuemin="0" aria-valuemax="100" style="width: {{ gamification_progress }}%">
                        <span class="sr-only">{{ gamification_progress }} Complete (success)</span>
                    </div>
                </div>
                <div class="progress-percentage text-right">{{ 'XPercent'|get_lang|format(gamification_progress) }}</div>
            </div>
        </div>
        <div class="show-progress">
            <div class="title">
                {{ 'ShowProgress'|get_lang }}
            </div>
            <div class="progress-course">
                <ul class="list-course">
                    {% for session in sessions %}
                        <li><a href="{{ _p.self ~ '?' ~ {"session_id": session.getId}|url_encode() }}" class="list-course-item {{ current_session and session.getId == current_session.getId ? 'active' }}">
                                <i class="fa fa-chevron-circle-right"></i> {{ session.getName }}
                            </a>
                        </li>
                    {% endfor %}
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        {% if current_session %}
            <div class="session">
                <div class="title"><i class="fa fa-square"></i> {{ current_session.getName() }}</div>
                <div class="panel-body">
                    {% for course_id, course in session_data %}
                        {% if session_data|length > 1 %}
                            <h3 class="title-course"><img src="{{ 'blackboard_blue.png'|icon(32) }}"/> {{ course.title }}</h3>
                            {% endif %}

                        <div class="panel-group" id="course-accordion" role="tablist" aria-multiselectable="true">
                            {% for stats_url in course.stats %}
                                {% if course.stats|length > 1 %}
                                    {% set panel_id = course_id ~ '-' ~ loop.index %}

                                    <div class="panel panel-default">
                                        <div class="panel-heading" role="tab" id="heading-{{ panel_id }}">
                                            <h4 class="panel-title">
                                                <a role="button" data-toggle="collapse" data-parent="#course-accordion" href="#collapse-{{ panel_id }}" aria-expanded="true" aria-controls="collapse-{{ panel_id }}">
                                                    {{ stats_url.0 }}
                                                </a>
                                            </h4>
                                        </div>
                                        <div id="collapse-{{ panel_id }}" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="heading-{{ panel_id }}">
                                            <div class="panel-body">
                                {% endif %}

                                <div class="embed-responsive embed-responsive-16by9">
                                    <iframe src="{{ _p.web_main ~ stats_url.1 }}"></iframe>
                                </div>

                                {% if course.stats|length > 1 %}
                                            </div>
                                        </div>
                                    </div>
                                {% endif %}
                            {% endfor %}
                        </div>
                    {% endfor %}
                </div>
            </div>
        {% endif %}
    </div>
</div>
