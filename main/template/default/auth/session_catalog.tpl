{% extends template ~ "/layout/main.tpl" %}

{% block body %}
<script type="text/javascript">
    $().ready(function() {
        $('#date').datepicker({
            dateFormat: 'yy-mm-dd'
        });

        $('#list-course').click(function(e) {
            e.preventDefault();
            var tempTarget = e.target.toString().split('#');
            tempTarget = '#' + tempTarget[1];
            // use the target of the link as the ID of the element to find
            var target = $(tempTarget);
            var targetContent = target.find('.list');

            if (targetContent.is(':empty')) {
                var idParts = tempTarget.split('-');

                var sessionId = parseInt(idParts[1], 10);

                $.ajax('{{ web_session_courses_ajax_url }}', {
                    data: {
                        a: 'display_sessions_courses',
                        session: sessionId
                    },
                    dataType: 'json',
                    success: function(response) {
                        var coursesUL = '';
                        $.each(response, function(index, course) {
                            coursesUL += '<li><img src="{{ _p.web }}main/img/check.png"/> <strong>' + course.name + '</strong>';

                            if (course.coachName != '') {
                                coursesUL += ' (' + course.coachName + ')';
                            }

                            coursesUL += '</li>';
                        });

                        targetContent.html('<ul class="items-session">' + coursesUL + '</ul>');
                        target.css({
                            height: targetContent.outerHeight()
                        }).addClass(' in');
                    }
                });
            } else {
                target.addClass(' in');
            }
        });
    });
</script>

<div class="col-md-3">
    {% if show_courses %}
    <div class="panel panel-default">
        <div class="panel-body">
            {% if not hidden_links %}
            <form class="form-search" method="post" action="{{ course_url }}">
                <input type="hidden" name="sec_token" value="{{ search_token }}">
                <input type="hidden" name="search_course" value="1" />
                <div class="form-group">
                    <input  type="text" name="search_term" class="form-control"/>
                    <button class="btn btn-block btn-default" type="submit"><i class="fa fa-search"></i> {{ 'Search' | get_lang }}</button>
                </div>
            </form>
            {% endif %}

            {% if course_category_list is not empty %}
            <a class="btn btn-block btn-default" href="{{ api_get_self }}?action=display_random_courses">{{ 'RandomPick' | get_lang }}</a>
            {% endif %}
        </div>
    </div>

    {% if coursesCategoriesList is not empty %}
        <div class="sidebar-nav">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ 'CourseCategories' | get_lang }}
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
                {{ 'Sessions' | get_lang }}
            </div>
            <div class="panel-body">
                <form class="form-search" method="post" action="{{ api_get_self }}?action=display_sessions">
                    <div class="form-group">
                        <input type="date" name="date" id="date" class="form-control" value="{{ search_date }}" readonly>
                        <button class="btn btn-block btn-default" type="submit"><i class="fa fa-search"></i> {{ 'Search' | get_lang }}</button>
                    </div>
                </form>
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
                        <div class="tutor">
                            <img src="{{ 'teacher.png' | icon(22) }}" width="16">
                            {{ 'GeneralCoach' | get_lang }} {{ session.coach_name }}
                        </div>
                        {% endif %}

                        {% if session.requirements %}
                            <h4>{{ 'Requirements' | get_lang }}</h4>
                            <p>
                            {% for requirement in session.requirements %}
                                {{ requirement.name  }}
                            {% endfor %}
                            </p>
                        {% endif %}

                        {% if session.dependencies %}
                            <h4>{{ 'Dependencies' | get_lang }}</h4>
                            <p>
                            {% for dependency in session.dependencies %}
                                {{ dependency.name  }}
                            {% endfor %}
                            </p>
                        {% endif %}

                        <a id="list-course" class="btn btn-default" data-toggle="collapse" href="#session-{{ session.id }}-courses">
                            {{ 'CourseList' | get_lang }}
                        </a>

                        <div class="collapse" id="session-{{ session.id }}-courses">
                            <div class="list"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        {% if session.show_description %}
                        <div class="buttom-subscribed">
                            <a class="ajax btn btn-large btn-info" href="{{ _p.web_ajax }}session.ajax.php?a=get_description&session={{ session.id }}">
                                {{ 'Description' | get_lang }}
                            </a>
                        </div>
                        {% endif %}

                        <div class="buttom-subscribed">
                            {% if session.is_subscribed %}
                                {{ already_subscribed_label }}
                            {% else %}
                                {{ session.subscribe_button }}
                            {% endif %}
                        </div>
                        <div class="time">
                            <img src="{{ 'agenda.png' | icon(22) }}"> {{ session.date }}
                        </div>
                    </div>
                </div>

            </div>
        </div>
    {% endfor %}
    {{ catalog_pagination }}
</div>

{% endblock %}
