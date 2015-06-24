{% extends template ~ "/layout/main.tpl" %}

{% block body %}
    <script type="text/javascript">
        $(document).ready(function () {
            $('#date').datepicker({
                dateFormat: 'yy-mm-dd'
            });

            $('a[data-session]').on('click', function (e) {
                e.preventDefault();

                var link = $(this),
                        sessionId = parseInt(link.data('session')),
                        collapsible = $('#collapse-' + sessionId),
                        courseList = link.data('courses') || [];

                if (courseList.length === 0) {
                    var getCourseList = $.getJSON(
                            '{{ _p.web_ajax }}course.ajax.php',
                            {
                                a: 'display_sessions_courses',
                                session: sessionId
                            }
                    );

                    $.when(getCourseList).done(function (courses) {
                        courseList = courses;
                        link.data('courses', courses);

                        var coursesUL = '';

                        $.each(courseList, function (index, course) {
                            coursesUL += '<li><img src="{{ _p.web }}main/img/check.png"/> <strong>' + course.name + '</strong>';

                            if (course.coachName !== '') {
                                coursesUL += ' (' + course.coachName + ')';
                            }

                            coursesUL += '</li>';
                        });

                        collapsible.html('<div class="panel-body"><ul class="list-unstyled items-session">' + coursesUL + '</ul></div>');

                        collapsible.collapse('show');
                    });
                } else {
                    collapsible.collapse('toggle');
                }
            });

            $('.collapse').collapse('hide');
        });
    </script>

    <div class="col-md-12">
        <!-- view catalog course -->
        {% if show_courses %}
            <div class="panel panel-default">
                <div class="panel-heading">{{ "Courses"|get_lang }}</div>
                <div class="panel-body">
                    {% if not hidden_links %}
                        <div class="row">
                            <div class="col-xs-12">
                                <form class="form-search" method="post" action="{{ course_url }}">
                                    <div class="input-group">
                                        <input type="text" name="search_term" class="form-control" />
                                        <input type="hidden" name="sec_token" value="{{ search_token }}">
                                        <input type="hidden" name="search_course" value="1" />
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="submit"><i class="fa fa-search"></i> {{ 'Search'|get_lang }}</button>
                                        </span>
                                    </div>
                                </form>
                            </div>
                        </div>
                    {% endif %}

                    {% if course_category_list is not empty %}
                        <br>
                        <a class="btn btn-block btn-default" href="{{ _p.web_self }}?action=display_random_courses">{{ 'RandomPick'|get_lang }}</a>
                    {% endif %}
                </div>
            </div>

            {% if coursesCategoriesList is not empty %}
                <div class="sidebar-nav">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            {{ 'CourseCategories'|get_lang }}
                        </div>
                        <div class="panel-body">
                            <ul class="list-categories">
                                {{ coursesCategoriesList }}
                            </ul>
                        </div>
                    </div>
                </div>
            {% endif %}
        {% endif %}
        <!-- end view catalog course -->
        <!-- view catalog sessions -->
        {% if show_sessions %}

            <div class="section-tile">{{ 'Sessions'|get_lang }}</div>
            <div class="row">
                <div class="col-md-4">
                    <form class="form-horizontal" method="post" action="{{ _p.web_self }}?action=display_sessions">
                        <div class="form-group">
                            <label class="col-sm-3">{{ "ByDate"|get_lang }}</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <input type="date" name="date" id="date" class="form-control" value="{{ search_date }}" readonly>
                                    <span class="input-group-btn">
                                        <button class="btn btn-default" type="submit"><i class="fa fa-search"></i> {{ 'Search'|get_lang }}</button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-md-4">
                    <form class="form-horizontal" method="post" action="{{ _p.web_self }}?action=search_tag">
                        <div class="form-group">
                            <label class="col-sm-4">{{ "ByTag"|get_lang }}</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="text" name="search_tag" class="form-control" value="{{ search_tag }}" />
                                    <span class="input-group-btn">
                                        <button class="btn btn-default" type="submit"><i class="fa fa-search"></i> {{ 'Search'|get_lang }}</button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        {% endif %}
    </div>
<section id="session-list">
    <div class="col-md-12">
        <div class="row">
            {% for session in sessions %}
            <div class="col-md-4">
                <div class="item-content" id="session-{{ session.id }}">
                    <div class="img-session">{{ session.extra_field.image }}</div>
                    <div class="title-session"><h3>{{ session.name }}</h3></div>
                    <div class="author-session">
                        <i class="fa fa-user"></i>
                        {{ session.coach_name }}
                    </div>
                    <div class="date-session">
                        <i class="fa fa-calendar-o"></i>
                        {{ session.date }}
                    </div>
                    <div class="tags-session">
                        <i class="fa fa-tags"></i>
                    </div>
                    <div class="requirements">
                    {% if session.requirements %}
                    <h4>{{ 'Requirements'|get_lang }}</h4>
                    <p>
                        {% for requirement in session.requirements %}
                        {{ requirement.name  }}
                        {% endfor %}
                    </p>
                    {% endif %}

                    {% if session.dependencies %}
                    <h4>{{ 'Dependencies'|get_lang }}</h4>
                    <p>
                        {% for dependency in session.dependencies %}
                        {{ dependency.name  }}
                        {% endfor %}
                    </p>
                    </div>
                    {% endif %}
                    <div class="options">
                        <a href="{{ _p.web ~ 'session/' ~ session.id ~ '/about/' }}" class="btn btn-block btn-info">
                            <i class="fa fa-info-circle"></i> {{ "SeeInformation"|get_lang }}
                        </a>
                        <div class="buttom-subscribed">
                            {% if session.is_subscribed %}
                            {{ already_subscribed_label }}
                            {% else %}
                            {{ session.subscribe_button }}
                            {% endif %}
                        </div>
                    </div>
                </div>
            </div></div>
            {% endfor %}

    </div>
        {{ catalog_pagination }}
    </div>
</section>
<!-- end view catalog session -->
{% endblock %}
