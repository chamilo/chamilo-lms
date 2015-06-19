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

<div class="col-md-3">
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

    {% if show_sessions %}
    <div class="sidebar-nav">
        <div class="panel panel-default">
            <div class="panel-heading">
                {{ 'Sessions'|get_lang }}
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-xs-12">
                        <form class="form-search" method="post" action="{{ _p.web_self }}?action=display_sessions">
                            <fieldset>
                                <legend>{{ "ByDate"|get_lang }}</legend>
                                <div class="input-group">
                                    <input type="date" name="date" id="date" class="form-control" value="{{ search_date }}" readonly>
                                            <span class="input-group-btn">
                                                <button class="btn btn-default" type="submit"><i class="fa fa-search"></i> {{ 'Search'|get_lang }}</button>
                                            </span>
                                </div>
                            </fieldset>
                        </form>
                        <br>
                        <form class="form-search" method="post" action="{{ _p.web_self }}?action=search_tag">
                            <fieldset>
                                <legend>{{ "ByTag"|get_lang }}</legend>
                                <div class="input-group">
                                    <input type="text" name="search_tag" class="form-control" value="{{ search_tag }}" />
                                            <span class="input-group-btn">
                                                <button class="btn btn-default" type="submit"><i class="fa fa-search"></i> {{ 'Search'|get_lang }}</button>
                                            </span>
                                </div>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {% endif %}
</div>
<div class="col-md-9">
    {% for session in sessions %}
    <div class="panel panel-default" id="panel-{{ session.id }}">
        <div class="panel-heading">
            {{ session.icon }} {{ session.name }}
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-9">
                    {% if show_tutor %}
                    <p class="tutor">
                        <img src="{{ 'teacher.png' | icon(22) }}" width="16">
                        {{ 'GeneralCoach'|get_lang }} {{ session.coach_name }}
                    </p>
                    {% endif %}

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
                    {% endif %}

                    <div class="panel-group" role="tablist">
                        <div class="panel panel-default">
                            <div class="panel-heading" role="tab" id="heading-session-{{ session.id }}">
                                <h4 class="panel-title">
                                    <a class="collapsed" data-session="{{ session.id }}" data-toggle="false" href="#collapse-{{ session.id }}" aria-expanded="true" aria-controls="collapse-{{ session.id }}">
                                        {{ 'CourseList'|get_lang }}
                                    </a>
                                </h4>
                            </div>
                            <div id="collapse-{{ session.id }}" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="heading-session-{{ session.id }}"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    {% if session.show_description %}
                    <p class="buttom-subscribed">
                        <a class="ajax btn btn-large btn-info" href="{{ _p.web_ajax }}session.ajax.php?a=get_description&session={{ session.id }}">
                            {{ 'Description'|get_lang }}
                        </a>
                    </p>
                    {% endif %}

                    <p>
                        <a href="{{ _p.web ~ 'session/' ~ session.id ~ '/about/' }}" class="btn btn-block btn-info">
                            <i class="fa fa-info-circle"></i> {{ "SeeInformation"|get_lang }}
                        </a>
                    </p>

                    <p class="buttom-subscribed">
                        {% if session.is_subscribed %}
                        {{ already_subscribed_label }}
                        {% else %}
                        {{ session.subscribe_button }}
                        {% endif %}
                    </p>
                    <p class="time">
                        <img src="{{ 'agenda.png' | icon(22) }}"> {{ session.date }}
                    </p>
                </div>
            </div>
        </div>
    </div>
    {% else %}
    {{ message }}
    {% endfor %}
    {{ catalog_pagination }}
</div>

{% endblock %}