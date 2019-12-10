{% set fold_session_category = 'user_portal_foldable_session_category'|api_get_configuration_value %}

<div class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="col-md-2">
                {% if fold_session_category %}
                    <a class="thumbnail" role="button" data-toggle="collapse" aria-expanded="false"
                       href="#collapse-category-{{ session_category.id }}"
                       aria-controls="collapse-category-{{ session_category.id }}">
                        <img src="{{ "sessions_category.png"|icon(48) }}" width="48" height="48"
                             alt="{{ session_category.title }}" title="{{ session_category.title }}">
                    </a>
                {% else %}
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

                {% if fold_session_category %}
                    <a role="button" data-toggle="collapse" href="#collapse-category-{{ session_category.id }}"
                       aria-expanded="false" aria-controls="collapse-category-{{ session_category.id }}">
                        <h4 class="title">{{ session_category.title }}</h4>
                    </a>
                {% else %}
                    <h4 class="title">{{ session_category.title }}</h4>
                {% endif %}

                {% if session_category.subtitle %}
                    <div class="subtitle-session">{{ session_category.subtitle }}</div>
                {% endif %}
            </div>
        </div>
        {# session_category.sessions is generated with the session.tpl #}
        {% if fold_session_category %}
            <div class="collapse" id="collapse-category-{{ session_category.id }}">
                {{ session_category.sessions }}
            </div>
        {% else %}
            {{ session_category.sessions }}
        {% endif %}
    </div>
</div>
