<div id="buy-courses-tabs">
    {% if sessions_are_included %}
        <ul class="nav nav-tabs buy-courses-tabs" role="tablist">
            {% if coursesExist %}
            <li id="buy-courses-tab" class="{{ showing_courses ? 'active' : '' }}" role="presentation">
                <a href="subscription_course_catalog.php" aria-controls="buy-courses" role="tab">{{ 'Courses'|get_lang }}</a>
            </li>
            {% endif %}
            {% if sessionExist %}
            <li id="buy-sessions-tab" class="{{ showing_sessions ? 'active' : '' }}" role="presentation">
                <a href="subscription_session_catalog.php" aria-controls="buy-sessions" role="tab">{{ 'Sessions'|get_lang }}</a>
            </li>
            {% endif %}
        </ul>
    {% endif %}

    <div class="tab-content">
        <div class="tab-pane active" aria-labelledby="buy-sessions-tab" role="tabpanel">
            <div class="row">
                <div class="col-md-3">
                    {{ search_filter_form }}
                </div>
                <div class="col-md-9">
                    <div class="row grid-courses">
                        {% if showing_courses %}
                            {% for course in courses %}
                                <div class="col-md-4 col-sm-6">
                                    <article class="items-course">
                                        <div class="items-course-image">
                                            <figure  class="img-responsive">
                                                <img alt="{{ course.title }}"
                                                     class="img-responsive"
                                                     src="{{ course.course_img ? course.course_img : 'session_default.png'|icon() }}">
                                            </figure>
                                        </div>
                                        <div class="items-course-info">
                                            {% set course_description_url = _p.web_ajax ~ 'course_home.ajax.php?' ~ {'code': course.code, 'a': 'show_course_information'}|url_encode() %}
                                            <h4 class="title">
                                                <a class="ajax" href="{{ course_description_url }}"
                                                   data-title="{{ course.title }}">{{ course.title }}</a>
                                            </h4>
                                            <ul class="list-unstyled">
                                                {% for teacher in course.teachers %}
                                                    <li><em class="fa fa-user"></em> {{ teacher }}</li>
                                                {% endfor %}
                                            </ul>
                                            {% if course.enrolled == "YES" %}
                                                <div class="alert alert-success">
                                                    <em class="fa fa-check-square-o fa-fw"></em> {{ 'TheUserIsAlreadyRegisteredInTheCourse'|get_plugin_lang('BuyCoursesPlugin') }}
                                                </div>
                                            {% elseif course.enrolled == "NO" %}
                                                <div class="toolbar">
                                                    <a class="ajax btn btn-info btn-block btn-sm" title=""
                                                       href="{{ course_description_url }}"
                                                       data-title="{{ course.title }}">
                                                        <em class="fa fa-file-text"></em> {{ 'SeeDescription'|get_plugin_lang('BuyCoursesPlugin') }}
                                                    </a>
                                                    <a class="btn btn-success btn-block btn-sm" title=""
                                                       href="{{ _p.web_plugin ~ 'buycourses/src/subscription_process.php?' ~ {'i': course.id, 't': 1}|url_encode() }}">
                                                        <em class="fa fa-shopping-cart"></em> {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                                                    </a>
                                                </div>
                                            {% elseif course.enrolled == "TMP" %}
                                                <div class="alert alert-info">{{ 'WaitingToReceiveThePayment'|get_plugin_lang('BuyCoursesPlugin') }}</div>
                                            {% endif %}
                                        </div>
                                    </article>
                                </div>
                            {% endfor %}
                        {% endif %}

                        {% if showing_sessions %}
                            {% for session in sessions %}
                                <div class="col-md-4 col-sm-6">
                                    <article class="items-course">
                                        <div class="items-course-image">
                                            <figure  class="img-responsive">
                                                <img alt="{{ session.name }}" class="img-responsive"
                                                     src="{{ session.image ? session.image : 'session_default.png'|icon() }}">
                                            </figure>
                                        </div>
                                        <div class="items-course-info">
                                            <h4 class="title">
                                                <a href="{{ _p.web ~ 'session/' ~ session.id ~ '/about/' }}">{{ session.name }}</a>
                                            </h4>
                                            {% if 'show_session_coach'|api_get_setting == 'true' %}
                                                <p><em class="fa fa-user fa-fw"></em> {{ session.coach }}</p>
                                            {% endif %}
                                            <p>
                                                <em class="fa fa-calendar fa-fw"></em>
                                                {% if session.duration %}
                                                    {{ 'SessionDurationXDaysTotal'|get_lang|format(session.duration) }}
                                                {% else %}
                                                    {{ session.dates.display }}
                                                {% endif %}
                                            </p>
                                            {% if session.enrolled == "YES" %}
                                                <div class="alert alert-success">
                                                    <em class="fa fa-check-square-o fa-fw"></em> {{ 'TheUserIsAlreadyRegisteredInTheSession'|get_plugin_lang('BuyCoursesPlugin') }}
                                                </div>
                                            {% elseif session.enrolled == "NO" %}
                                                <div class="toolbar">
                                                    <a class="btn btn-success btn-block btn-sm"
                                                       href="{{ _p.web_plugin ~ 'buycourses/src/subscription_process.php?' ~ {'i': session.id, 't': 2}|url_encode() }}">
                                                        <em class="fa fa-shopping-cart"></em> {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                                                    </a>
                                                </div>
                                            {% elseif session.enrolled == "TMP" %}
                                                <div class="alert alert-info">{{ 'WaitingToReceiveThePayment'|get_plugin_lang('BuyCoursesPlugin') }}</div>
                                            {% endif %}
                                        </div>
                                    </article>
                                </div>
                            {% endfor %}
                        {% endif %}
                    </div>
                    {{ pagination }}
                </div>
            </div>
        </div>
    </div>
</div>
