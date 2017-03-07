<div class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="col-md-2">
                {% if session_category.show_actions %}
                    <a href="{{ _p.web_main ~ 'session/session_category_edit.php?id=' ~ session_category.id }}"
                       class="thumbnail">
                        <img src="{{ "sessions_category.png"|icon(48) }}" width="48" height="48"
                             alt="{{ session_category.title }}" title="{{ session_category.title }}">
                    </a>
                {% else %}
                    <img src="{{ "sessions_category.png"|icon(48) }}" width="48" height="48"
                         alt="{{ session_category.title }}" title="{{ session_category.title }}">
                {% endif %}
            </div>
            <div class="col-md-10">
                {% if session_category.show_actions %}
                    <div class="pull-right">
                        <a href="{{ _p.web_main ~ 'session/session_category_edit.php?id=' ~ session_category.id }}">
                            <img src="{{ "edit.png"|icon(22) }}" width="22" height="22" alt="{{ "Edit"|get_lang }}"
                                 title="{{ "Edit"|get_lang }}">
                        </a>
                    </div>
                {% endif %}

                <h4 class="title">{{ session_category.title }}</h4>
                {% if session_category.subtitle %}
                    <div class="subtitle-session">{{ session_category.subtitle }}</div>
                {% endif %}
            </div>
        </div>

        {# session_category.sessions is generated with the session.tpl #}
        {{ session_category.sessions }}
    </div>
</div>
