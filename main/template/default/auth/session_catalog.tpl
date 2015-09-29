{% extends template ~ "/layout/main.tpl" %}

{% block body %}
    <script type="text/javascript">
        $(document).ready(function () {
            $('#date').datepicker({
                dateFormat: 'yy-mm-dd'
            });
        });
    </script>

    <div class="col-md-12">
        <div class="row">
            {% if show_courses %}
                <div class="col-md-4">
                    <div class="section-title-catalog">{{ 'Courses'|get_lang }}</div>
                    {% if not hidden_links %}
                        <form class="form-horizontal" method="post" action="{{ course_url }}">
                            <div class="form-group">
                                <div class="col-sm-12">
                                    <input type="hidden" name="sec_token" value="{{ search_token }}">
                                    <input type="hidden" name="search_course" value="1" />
                                    <div class="input-group">
                                        <input type="text" name="search_term" class="form-control" />
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="submit"><i class="fa fa-search"></i> {{ 'Search'|get_lang }}</button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </form>
                    {% endif %}
                </div>
            {% endif %}

            <div class="col-md-8">
                {% if show_sessions %}
                    <div class="section-title-catalog">{{ 'Sessions'|get_lang }}</div>

                    <div class="row">
                        <div class="col-md-6">
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
                        <div class="col-md-6">
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
        </div>
    </div>

    <section id="session-list">
        <div class="col-md-12">
            <div class="row">
                {% for session in sessions %}
                    <div class="col-md-3 session-col">
                        <div class="item" id="session-{{ session.id }}">
                            <img src="{{ session.image ? _p.web_upload ~ session.image : _p.web_img ~ 'session_default.png' }}">

                            <div class="information-item">
                                <h3 class="title-session">
                                    <a href="{{ _p.web ~ 'session/' ~ session.id ~ '/about/' }}" title="{{ session.name }}">
                                        {{ session.name }}
                                    </a>
                                </h3>
                                <ul class="list-unstyled">
                                    {% if show_tutor %}
                                        <li class="author-session">
                                            <i class="fa fa-user"></i> {{ session.coach_name }}
                                        </li>
                                    {% endif %}
                                    <li class="date-session">
                                        <i class="fa fa-calendar-o"></i> {{ session.date }}
                                    </li>
                                    {% if session.tags %}
                                        <li class="tags-session">
                                            <i class="fa fa-tags"></i> {{ session.tags|join(', ')}}
                                        </li>
                                    {% endif %}
                                </ul>

                                <div class="options">
                                    {% if not _u.logged %}
                                        <p>
                                            <a class="btn btn-info btn-block btn-sm" href="{{ "#{_p.web}session/#{session.id}/about/" }}" title="{{ session.name }}">{{ 'SeeCourseInformationAndRequirements'|get_lang }}</a>
                                        </p>
                                    {% else %}
                                        <p>
                                            <a class="btn btn-info btn-block btn-sm" role="button" data-toggle="popover" id="session-{{ session.id }}-sequences">{{ 'SeeSequences'|get_lang }}</a>
                                        </p>
                                        <p class="buttom-subscribed">
                                            {% if session.is_subscribed %}
                                                {{ already_subscribed_label }}
                                            {% else %}
                                                {{ session.subscribe_button }}
                                            {% endif %}
                                        </p>
                                    {% endif %}
                                </div>
                            </div>

                            {% if _u.logged %}
                                <script>
                                    $('#session-{{ session.id }}-sequences').popover({
                                        placement: 'bottom',
                                        html: true,
                                        trigger: 'click',
                                        content: function () {
                                            var content = '';

                                            {% if session.sequences %}
                                                {% for sequence in session.sequences %}
                                                    content += '<p class="lead">{{ sequence.name }}</p>';

                                                    {% if sequence.requirements %}
                                                        content += '<p><i class="fa fa-sort-amount-desc"></i> {{ 'RequiredSessions'|get_lang }}</p>';
                                                        content += '<ul>';

                                                        {% for requirement in sequence.requirements %}
                                                            content += '<li>';
                                                            content += '<a href="{{ _p.web ~ 'session/' ~ requirement.id ~ '/about/' }}">{{ requirement.name }}</a>';
                                                            content += '</li>';
                                                        {% endfor %}

                                                        content += '</ul>';
                                                    {% endif %}

                                                    {% if sequence.dependencies %}
                                                        content += '<p><i class="fa fa-sort-amount-desc"></i> {{ 'DependentSessions'|get_lang }}</p>';
                                                        content += '<ul>';

                                                        {% for dependency in sequence.dependencies %}
                                                            content += '<li>';
                                                            content += '<a href="{{ _p.web ~ 'session/' ~ dependency.id ~ '/about/' }}">{{ dependency.name }}</a>';
                                                            content += '</li>';
                                                        {% endfor %}

                                                        content += '</ul>';
                                                    {% endif %}

                                                    {% if session.sequences|length > 1 %}
                                                        content += '<hr>';
                                                    {% endif %}
                                                {% endfor %}
                                            {% else %}
                                                content = "{{ 'NoDependencies'|get_lang }}";
                                            {% endif %}

                                            return content;
                                        }
                                    });
                                </script>
                            {% endif %}
                        </div>
                    </div>
                {% else %}
                    <div class="col-xs-12">
                        <div class="alert alert-warning">
                            {{ 'NoResults'|get_lang }}
                        </div>
                    </div>
                {% endfor %}
            </div>
        </div>
    </section>

    {{ catalog_pagination }}

{% endblock %}
