{% if current_sessions_block.sessions|length > 0 %}
<div class="my-courses-ranking">
    <div class="row">
        <div class="col-xs-12 col-md-12">
            <h4 class="title-section">{{ "MyCurrentCourses"|get_plugin_lang('CurrentSessionsBlockPlugin') }}</h4>
            <a href="{{ _p.web ~ 'user_portal.php' }}" class="more">{{ 'SeeMore'|get_lang }}</a>
        </div>
    </div>
    <div class="current-block">
        <div class="row">
            {% for session in current_sessions_block.sessions %}
            <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
                <h2 class="title-course">{{ session.name }}</h2>
                <div class="card">
                    <div class="front">
                        <div id="items-img-1" class="img-items-course">
                            <img class="img-responsive" src="{{ _p.web_upload ~ session.image }}">
                        </div>
                    </div>
                    <div class="back">

                        <div class="text-items-course">
                            <div class="row">
                                <div class="col-xs-6 col-md-6 col-lg-6">
                                    {% if session.start_date %}
                                    <p class="status">{{ "Start"|get_plugin_lang('CurrentSessionsBlockPlugin') }}</p>
                                    <p class="date">{{ session.start_date }}</p>
                                    {% endif %}
                                </div>
                                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                                    {% if session.end_date %}
                                    <p class="status">{{ "End"|get_plugin_lang('CurrentSessionsBlockPlugin') }}</p>
                                    <p class="date">{{ session.end_date }}</p>
                                    {% endif %}
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-md-12 col-lg-12">
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: {{ session.progress }}%;">
                                            <span class="sr-only">{{ session.progress }}% Complete</span>
                                        </div>
                                    </div>
                                    <div class="text-progress">{{ session.progress }}%</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-md-12">
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
                                    <div class="botton-items">
                                        <a href="{{ session.link }}" class="btn btn-primary">{{ "Continue"|get_lang }}</a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            {% endfor %}
        </div>
    </div>

</div>
{% endif %}
