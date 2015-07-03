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

            <div class="col-md-12">
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
                    <div class="col-md-3">
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
                                <div class="requirements">
                                    {% if session.requirements %}
                                    <p><i class="fa fa-book"></i> {{ 'Requirements'|get_lang }}</p>
                                    <p>
                                        {% for requirement in session.requirements %}
                                        {{ requirement.name  }}
                                        {% endfor %}
                                    </p>
                                    {% endif %}

                                    {% if session.dependencies %}
                                    <p><i class="fa fa-book"></i> {{ 'Dependencies'|get_lang }}</p>
                                    <p>
                                        {% for dependency in session.dependencies %}
                                        {{ dependency.name  }}
                                        {% endfor %}
                                    </p>
                                    {% endif %}
                                </div>
                                <div class="options">

                                    <p class="buttom-subscribed">
                                        {% if session.is_subscribed %}
                                        {{ already_subscribed_label }}
                                        {% else %}
                                        {{ session.subscribe_button }}
                                        {% endif %}
                                    </p>
                                </div>
                            </div>


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
