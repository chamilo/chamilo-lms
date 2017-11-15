{% if _u.status == '5' %}
<!-- list courses in sessions users -->
<div class="grid-courses">
<div class="row">
    {% for item in all_courses %}
    <div class="col-xs-12 col-sm-6 col-md-4">
        <div class="items">
            <div class='session'>
                <h6 class='session-name'><i class="fa fa-book" aria-hidden="true"></i> {{ item.session.title }}</h6>
            </div>
            <div class="image">
                <img src="{{ item.image }}" class="img-responsive">
                {% if item.category != '' %}
                <span class="category">{{ item.session.category_id }}</span>
                <div class="cribbon"></div>
                {% endif %}
                <div class="black-shadow">
                    <div class="author-card">
                    {% for teacher in item.teachers %}
                        {% set counter = counter + 1 %}
                        {% if counter <= 3 %}
                        <a href="{{ teacher.url }}" class="ajax" data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                            <img src="{{ teacher.avatar }}"/>
                        </a>
                        {% endif %}
                    {% endfor %}
                    </div>
                    <div class="session-date">
                        <i class="fa fa-calendar" aria-hidden="true"></i> {{ item.session.date }}
                    </div>
                </div>
            </div>
            <div class="description">
                <h4 class="title">
                    {{ item.title }}
                </h4>
                <div class="notifications">{{ item.notifications }}</div>
            </div>
        </div>
    </div>
    {% endfor %}
    </div>
</div>
{% else %}
<div class="grid-courses">
    <div class="row">
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
    </div>
</div>
{% endif %}
