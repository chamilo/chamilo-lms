{% if current_sessions_block.sessions|length > 0 %}
    <h3 class="page-header">{{ "MyCurrentCourses"|get_lang }}</h3>
    <div class="row">
        {% for session in current_sessions_block.sessions %}
            <div class="col-sm-4">
                <h4 class="text-uppercase">{{ session.name }}</h4>
                <div class="well">
                    <div class="row text-uppercase">
                        <div class="col-xs-6 text-center">
                            {% if session.date_start != '0000-00-00' %}
                                <span>{{ "Start"|get_lang }}</span>
                                <span>{{ session.date_start }}</span>
                            {% endif %}
                        </div>
                        <div class="col-xs-6 text-center">
                            {% if session.date_end != '0000-00-00' %}
                                <span>{{ "End"|get_lang }}</span>
                                <span>{{ session.date_end }}</span>
                            {% endif %}
                        </div>
                    </div>
                    <div>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: {{ session.progress }}%;">
                                <span class="sr-only">{{ session.progress }}% Complete</span>
                            </div>
                        </div>
                        <p class="text-right"><small>{{ session.progress }}%</small></p>
                    </div>
                    <div>
                        {% if session.stars > 0%}
                            {% for i in 1..session.stars %}
                                <i class="fa fa-star fa-2x"></i>
                            {% endfor %}
                        {% endif %}
                        {% for i in 1..4 - session.stars %}
                            <i class="fa fa-star-o fa-2x"></i>
                        {% endfor %}
                    </div>
                    <div class="text-right">
                        <a href="#" class="btn btn-default" role="button">{{ "Continue"|get_lang }}</a>
                    </div>
                </div>
            </div>
        {% endfor %}
    </div>
{% endif %}
