{% for item in session %}

    <div class="col-xs-12 col-sm-6 col-md-4">
        <div class="items session {{ item.is_old ? 'old_session' : '' }} {{ item.is_future ? 'future_session' : '' }} ">
            <div class="image">
               <img class="img-responsive" src="{{ item.image ? _p.web_upload ~ item.image : _p.web_img ~ 'session_default.png' }}">
                <div class="black-shadow">
                    <div class="author-card">
                        <a href="{{ item.coach_url }}" class="ajax" data-title="{{ item.coach_name }}">
                            <img src="{{ item.coach_avatar }}"/>
                        </a>
                        <div class="teachers-details">
                            <h5>
                                <a href="{{ item.coach_url }}" class="ajax" data-title="{{ item.coach_name }}">
                                    {{ item.coach_name }}
                                </a>
                            </h5>
                        </div>
                    </div>
                </div>
                {% if item.edit_actions != '' %}
                    <div class="admin-actions">
                        {% if item.document == '' %}
                            <a class="btn btn-default btn-sm" href="{{ item.edit_actions }}">
                                <i class="fa fa-pencil" aria-hidden="true"></i>
                            </a>
                        {% else %}
                            <div class="btn-group" role="group">
                                <a class="btn btn-default btn-sm" href="{{ item.edit_actions }}">
                                    <i class="fa fa-pencil" aria-hidden="true"></i>
                                </a>
                                {{ item.document }}
                            </div>
                        {% endif %}
                    </div>
                {% endif %}
            </div>
            <div class="description">
                <h4 class="title">
                    <a href="{{ item.edit_actions }}">{{ item.title }}</a>

                </h4>
                <div>
                    <span><i class="fa fa-book" aria-hidden="true"></i> {{ item.num_courses }}</span>
                    <span><i class="fa fa-user" aria-hidden="true"></i> {{ item.num_users }}</span>
                </div>
            </div>
        </div>
    </div>
{% endfor %}
