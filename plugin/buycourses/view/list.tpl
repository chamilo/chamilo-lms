<script type='text/javascript' src="../js/buycourses.js"></script>

<link rel="stylesheet" type="text/css" href="../resources/css/style.css"/>

<script>
    $(function () {
        $('#buy-courses-tabs nav-tabs a').click(function (e) {
            e.preventDefault();

            $(this).tab('show');
        });
    });
</script>

<div id="buy-courses-tabs">
    {% if sessionsAreIncluded == "YES" %}
        <ul class="nav nav-tabs" role="tablist">
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
                {% if rmessage == "YES" %}
                    <div class="{{ class }}">
                        {{ responseMessage }}
                    </div>
                {% endif %}

                {% for course in courses %}
                    <div class="col-md-3">
                        <div class="items-course">
                            <div class="items-imagen">
                                <a class="ajax" rel="gb_page_center[778]" title="" href="{{ server }}plugin/buycourses/src/ajax.php?code={{ course.code }}">
                                    <img alt="" class="img-responsive" src="{{ server }}{{ course.course_img }}">
                                </a>
                            </div>
                            <div class="items-title">
                                <a class="ajax" rel="gb_page_center[778]" title="" href="{{ server }}plugin/buycourses/src/ajax.php?code={{ course.code }}">
                                    {{ course.title }}
                                </a>
                            </div>
                            <div class="items-teacher">
                                <i class="fa fa-user"></i> {{ 'Teacher'|get_lang }}: {{ course.teacher }}
                            </div>
                            <div class="items-status">
                                {% if course.enrolled == "YES" %}
                                    {{ 'TheUserIsAlreadyRegisteredInTheCourse'|get_plugin_lang('BuyCoursesPlugin') }}
                                {% endif %}
                                {% if course.enrolled == "TMP" %}
                                    {{ 'WaitingToReceiveThePayment'|get_plugin_lang('BuyCoursesPlugin') }}
                                {% endif %}
                            </div>
                            <div class="items-price">
                                {{ course.price }} {{ currency }}
                            </div>
                            <div class="items-button">
                                <div class="btn-group btn-group-sm">
                                    <a class="ajax btn btn-primary" title="" href="{{ server }}plugin/buycourses/src/ajax.php?code={{ course.code }}">
                                        <i class="fa fa-file-text"></i> {{ 'Description'|get_lang }}
                                    </a>
                                    {% if course.enrolled == "NO" %}
                                        <a class="btn btn-success" title="" href="{{ server }}plugin/buycourses/src/process.php?code={{ course.id }}">
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

        {% if sessionsAreIncluded == "YES" %}
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
                    <div class="col-md-12" id="session_results">
                        {% if rmessage == "YES" %}
                            <div class="{{ class }}">
                                {{ responseMessage }}
                            </div>
                        {% endif %}

                        {% for session in sessions %}
                            <div class="row">

                                <div class="col-md-12">
                                    <div class="categories-course-description">
                                        <h3>{{ session.name }}</h3>
                                        <h5>{{ 'From'|get_lang }} {{ session.access_start_date }} {{ 'Until'|get_lang }} {{ session.access_end_date }}</h5>
                                        {% if session.enrolled == "YES" %}
                                            <span class="label label-info">{{ 'TheUserIsAlreadyRegisteredInTheSession'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                                        {% endif %}
                                        {% if session.enrolled == "TMP" %}
                                            <span class="label label-warning">{{ 'WaitingToReceiveThePayment'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                                        {% endif %}
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="">
                                        {{ session.price }} {{ currency }}
                                    </div>

                                    <div class="btn-group">
                                        {% if session.enrolled == "NO" %}
                                            <a class="btn btn-success" title="" href="{{ server }}plugin/buycourses/src/process.php?scode={{ session.session_id }}">
                                                {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                                            </a>
                                        {% endif %}
                                    </div>
                                </div>
                                <div class="row">
                                    {% for course in session.courses %}
                                        <div class="col-md-3">
                                            <div class="span">
                                                <div class="thumbnail">
                                                    <a class="ajax" rel="gb_page_center[778]" title="" href="{{ server }}plugin/buycourses/src/ajax.php?code={{ course.code }}">
                                                        <img alt="" src="{{ server }}{{ course.course_img }}">
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="span4">
                                                <div class="categories-course-description">
                                                    <h3>{{ course.title }}</h3>
                                                    <h5>{{ 'Teacher'|get_lang }}: {{ course.teacher }}</h5>
                                                </div>
                                                {% if course.enrolled == "YES" %}
                                                    <span class="label label-info">{{ 'TheUserIsAlreadyRegisteredInTheCourse'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                                                {% endif %}
                                                {% if course.enrolled == "TMP" %}
                                                    <span class="label label-warning">{{ 'WaitingToReceiveThePayment'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                                                {% endif %}
                                            </div>
                                            <div class="span right">
                                                <div class="cleared"></div>
                                                <div class="btn-group right">
                                                    <a class="ajax btn btn-primary" title="" href="{{ server }}plugin/buycourses/src/ajax.php?code={{ course.code }}">
                                                        {{ 'Description'|get_lang }}
                                                    </a>
                                                </div>
                                            </div>

                                        {% endfor %}
                                    </div>
                                </div>
                            {% endfor %}
                        </div>
                    </div>
                </div>
            </div>
        {% endif %}
    </div>
</div>
