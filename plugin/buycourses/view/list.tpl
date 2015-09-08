<script type='text/javascript' src="../js/buycourses.js"></script>

<link rel="stylesheet" type="text/css" href="../resources/css/style.css"/>

<div id="buy-courses-tabs">
    {% if sessions_are_included %}
        <ul class="nav nav-tabs buy-courses-tabs" role="tablist">
            <li id="buy-courses-tab" class="active" role="presentation">
                <a href="#buy-courses" aria-controls="buy-courses" role="tab" data-toggle="tab">{{ 'Courses'|get_lang }}</a>
            </li>
            <li id="buy-sessions-tab" role="presentation">
                <a href="#buy-sessions" aria-controls="buy-sessions" role="tab" data-toggle="tab">{{ 'Sessions'|get_lang }}</a>
            </li>
        </ul>
    {% endif %}

    <div class="tab-content">
        <div id="buy-courses" class="tab-pane fade active in" aria-labelledby="buy-courses-tab" role="tabpanel">
            <div class="row">
                <div class="col-xs-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">{{ 'SearchFilter'|get_plugin_lang('BuyCoursesPlugin') }}</div>
                        <div class="panel-body">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{ 'CourseName'|get_lang }}:</label>
                                    <input class="form-control" type="text" class="name" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{ 'MinimumPrice'|get_plugin_lang('BuyCoursesPlugin') }}:</label>
                                    <input type="text" class="form-control"/>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{ 'MaximumPrice'|get_plugin_lang('BuyCoursesPlugin') }}:</label>
                                    <input type="text" class="form-control"/>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="top-button">
                                    <input type="button" class="btn btn-success btn-block" value="{{ 'Search'|get_lang }}" id="courses_filter" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row" id="course_results">
                {% for course in courses %}
                    <div class="col-md-3 col-sm-6">
                        <div class="thumbnail">
                            <img alt="{{ course.title }}" class="img-responsive" src="{{ course.course_img ? course.course_img : 'session_default.png'|icon() }}">
                            <div class="caption">
                                <h3>
                                    <a class="ajax" href="{{ _p.web_plugin ~ 'buycourses/src/ajax.php?' ~ {'code': course.code}|url_encode() }}">{{ course.title }}</a>
                                </h3>
                                <ul class="list-unstyled">
                                    {% for teacher in course.teachers %}
                                        <li><i class="fa fa-user"></i> {{ teacher }}</li>
                                    {% endfor %}
                                </ul>
                                {% if course.enrolled == "YES" %}
                                    <p>{{ 'TheUserIsAlreadyRegisteredInTheCourse'|get_plugin_lang('BuyCoursesPlugin') }}</p>
                                {% elseif course.enrolled == "TMP" %}
                                    <p>{{ 'WaitingToReceiveThePayment'|get_plugin_lang('BuyCoursesPlugin') }}</p>
                                {% endif %}
                                <p class="lead text-right">{{ course.currency }} {{ course.price }}</p>
                                <div class="text-center">
                                    <a class="ajax btn btn-primary" title="" href="{{ _p.web_plugin ~ 'buycourses/src/ajax.php?' ~ {'code': course.code}|url_encode() }}">
                                        <i class="fa fa-file-text"></i> {{ 'Description'|get_lang }}
                                    </a>
                                    {% if course.enrolled == "NO" %}
                                        <a class="btn btn-success" title="" href="{{ _p.web_plugin ~ 'buycourses/src/process.php?' ~ {'i': course.id, 't': 1}|url_encode() }}">
                                            <i class="fa fa-shopping-cart"></i> {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                                        </a>
                                    {% endif %}
                                </div>
                            </div>
                        </div>
                    </div>
                {% endfor %}
            </div>
        </div>

        {% if sessions_are_included %}
            <div id="buy-sessions" class="tab-pane fade" aria-labelledby="buy-sessions-tab" role="tabpanel">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        {{ 'SearchFilter'|get_plugin_lang('BuyCoursesPlugin') }}
                    </div>
                    <div class="panel-body">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ 'SessionName'|get_lang }}:</label>
                                <input type="text" class="form-control"/>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ 'MinimumPrice'|get_plugin_lang('BuyCoursesPlugin') }}:</label>
                                <input type="text" class="form-control"/>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>{{ 'MaximumPrice'|get_plugin_lang('BuyCoursesPlugin') }}:</label>
                            <input type="text" class="form-control"/>
                        </div>
                        <div class="col-md-3">
                            <div class="top-button">
                                <input type="button" class="btn btn-success btn-block" value="{{ 'Search'|get_lang }}" id="sessions_filter" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    {% for session in sessions %}
                        <div class="col-md-3 col-sm-6">
                            <div class="thumbnail">
                                <img alt="{{ session.name }}" class="img-responsive" src="{{ session.image ? session.image : 'session_default.png'|icon() }}">
                                <div class="caption">
                                    <h3>{{ session.name }}</h3>
                                    <p>{{ session.dates.display }}</p>
                                    <p class="lead text-right">{{ session.currency }} {{ session.price }}</p>
                                    <ul class="list-unstyled">
                                        {% for course in session.courses %}
                                            <li>
                                                <i class="fa fa-book fa-fw"></i> {{ course.title }}
                                                {% if course.coaches|length %}
                                                    <ul>
                                                        {% for coach in course.coaches %}
                                                            <li>{{ coach }}</li>
                                                        {% endfor %}
                                                    </ul>
                                                {% endif %}
                                            </li>
                                        {% endfor %}
                                    </ul>
                                    <div class="text-center">
                                        {% if session.enrolled == "YES" %}
                                            <span class="label label-info">{{ 'TheUserIsAlreadyRegisteredInTheSession'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                                        {% elseif session.enrolled == "NO" %}
                                            <a class="btn btn-success" href="{{ _p.web_plugin ~ 'buycourses/src/process.php?' ~ {'i': session.id, 't': 2}|url_encode() }}">
                                                <i class="fa fa-shopping-cart"></i> {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                                            </a>
                                        {% elseif session.enrolled == "TMP" %}
                                            <span class="label label-warning">{{ 'WaitingToReceiveThePayment'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                                        {% endif %}
                                    </div>
                                </div>
                            </div>
                        </div>
                    {% endfor %}
                </div>
            </div>
        {% endif %}
    </div>
</div>
