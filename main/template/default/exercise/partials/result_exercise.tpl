<div class="question-result">
    <div class="panel panel-default">
        <div class="panel-body">
            {% if 'save_titles_as_html'|api_get_configuration_value %}
                {{ data.title }}
            {% else %}
                <h3>{{ data.title }}</h3>
            {% endif %}

            <div class="row">
                <div class="col-md-3">
                    <div class="user-avatar">
                        <img src="{{ data.avatar }}">
                    </div>
                    <div class="user-info">
                        <strong>{{ data.name_url }}</strong><br>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="group-data">
                        <div class="list-data username">
                            <span class="item">{{ 'Username'|get_lang }}</span>
                            <i class="fa fa-fw fa-user" aria-hidden="true"></i> {{ data.username }}
                        </div>

                        {% if data.start_date %}
                            <div class="list-data start-date">
                                <span class="item">{{ 'StartDate'|get_lang }}</span>
                                <i class="fa fa-fw fa-calendar" aria-hidden="true"></i> {{ data.start_date }}
                            </div>
                        {% endif %}

                        {% if data.duration %}
                            <div class="list-data duration">
                                <span class="item">{{ 'Duration'|get_lang }}</span>
                                <i class="fa fa-fw fa-clock-o" aria-hidden="true"></i> {{ data.duration }}
                            </div>
                        {% endif %}

                        {% if data.ip %}
                            <div class="list-data ip">
                                <span class="item">{{ 'IP'|get_lang }}</span>
                                <i class="fa fa-fw fa-laptop" aria-hidden="true"></i> {{ data.ip }}
                            </div>
                        {% endif %}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
