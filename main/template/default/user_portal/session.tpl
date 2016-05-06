<div class="panel panel-default">
{% if not session.show_simple_session_info %}
    {% set collapsable = '' %}
    <div class="panel-heading">
        {% if session.course_list_session_style == 1 %}
            {# Classic #}
            <a href="{{ _p.web_main ~ 'session/index.php?session_id=' ~ session.id }}">
                <img id="session_img_{{ session.id }}" src="{{ "window_list.png"|icon(32) }}" width="32" height="32" alt="{{ session.title }}" title="{{ session.title }}" />
                {{ session.title }}
            </a>
        {% elseif session.course_list_session_style == 2 %}
            {# No link #}
            <img id="session_img_{{ session.id }}" src="{{ "window_list.png"|icon(32) }}" width="32" height="32" alt="{{ session.title }}" title="{{ session.title }}" />
            {{ session.title }}
        {% elseif session.course_list_session_style == 3 %}
            {# Foldable #}
            <a role="button" data-toggle="collapse" data-parent="#page-content" href="#collapse_{{ session.id }}" aria-expanded="false" >
                <img id="session_img_{{ session.id }}" src="{{ "window_list.png"|icon(32) }}" width="32" height="32" alt="{{ session.title }}" title="{{ session.title }}" />
                {{ session.title }}
            </a>
            {% set collapsable = 'collapse' %}
        {% endif %}

        {% if session.show_actions %}
            <div class="pull-right">
                <a href="{{ _p.web_main ~ "session/resume_session.php?id_session=" ~ session.id }}">
                    <img src="{{ "edit.png"|icon(22) }}" width="22" height="22" alt="{{ "Edit"|get_lang }}" title="{{ "Edit"|get_lang }}" />
                </a>
            </div>
        {% endif %}
    </div>
{% endif %}

<div class="sessions panel-body {{ collapsable }}" id="collapse_{{ session.id }}">
    {% if session.show_simple_session_info %}
        <div class="row">
            <div class="col-md-7">
                <h3>
                    {{ session.title ~ session.notifications }}
                </h3>
                {% if session.show_description %}
                    <div>
                        {{ session.description }}
                    </div>
                {% endif %}

                {% if session.subtitle %}
                    <small>{{ session.subtitle }}</small>
                {% endif %}

                {% if session.teachers %}
                    <h5 class="teacher-name">{{ "teacher.png"|icon(16) ~ session.teachers }}</h5>
                {% endif %}

                {% if session.coaches %}
                    <h5 class="teacher-name">{{ "teacher.png"|icon(16) ~ session.coaches }}</h5>
                {% endif %}
            </div>

            {% if session.show_actions %}
                <div class="col-md-5 text-right">
                    <a href="{{ _p.web_main ~ "session/resume_session.php?id_session=" ~ session.id }}">
                        <img src="{{ "edit.png"|icon(22) }}" width="22" height="22" alt="{{ "Edit"|get_lang }}" title="{{ "Edit"|get_lang }}">
                    </a>
                </div>
            {% endif %}
        </div>
    {% else %}
        <div class="row">
            <div class="col-md-12">
                {% if session.subtitle %}
                    <div class="subtitle-session">
                        <em class="fa fa-clock-o"></em> {{ session.subtitle }}
                    </div>
                {% endif %}
                {% if session.show_description %}
                    <div class="description-session">
                        {{ session.description }}
                    </div>
                {% endif %}
                <div class="sessions-items">
                    {% for item in session.courses %}
                        <div class="row">
                            <div class="col-md-2">
                                {% if item.link %}
                                    <a href="{{ item.link }}" class="thumbnail">{{ item.icon }}</a>
                                {% else %}
                                    {{ item.icon }}
                                {% endif %}
                            </div>
                            <div class="col-md-10">
                                {{ item.title }}
                                {% if item.coaches|length > 0 %}
                                    <img src="{{ 'teacher.png'|icon(16) }}" width="16" height="16">
                                    {% for coach in item.coaches %}
                                        {{ loop.index > 1 ? ' | ' }}
                                        <a href="{{ _p.web_ajax ~ 'user_manager.ajax.php?' ~ {'a': 'get_user_popup', 'user_id': coach.user_id}|url_encode() }}" data-title="{{ coach.full_name }}" class="ajax">
                                            {{ coach.full_name }}
                                        </a>
                                    {% endfor %}
                                {% endif %}
                            </div>
                        </div>
                    {% endfor %}
                </div>
            </div>
        </div>
    {% endif %}
    </div>
</div>
