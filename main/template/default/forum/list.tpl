{{ introduction_section }}
{% if data is not empty %}
{% for item in data %}
    <div class="category-forum" id="category_{{ item.id }}">
        <div class="pull-right">
            {{ item.tools }}
        </div>
        <h3>
            {{ 'forum_blue.png'|img(32) }}
            <a href="{{ item.url }}" title="{{ item.title }}">{{ item.title }}{{ item.icon_session }}</a>
        </h3>
        <div class="forum-description">
            {{ item.description }}
        </div>
    </div>
        {% for subitem in item.forums %}
            <div class="forum_display">
                <div class="panel panel-default forum">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="number-post">
                                    <a href="{{ forum.url }}" title="{{forum.title}}">
                                    {% if subitem.forum_image is not empty %}
                                        <img src="{{ subitem.forum_image }}" width="48px">
                                    {% else %}
                                        {% if subitem.forum_of_group == 0 %}
                                            {{ 'forum_group.png'|img(48) }}
                                        {% else %}
                                            {{ 'forum.png'|img(48) }}
                                        {% endif %}
                                    {% endif %}
                                    </a>
                                    <p>{{ 'ForumThreads'| get_lang }}: {{ subitem.number_threads }} </p>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="pull-right">
                                    <div class="toolbar">
                                        {{ subitem.tools }}
                                    </div>
                                </div>
                                <h3 class="title">
                                {{ 'forum_yellow.png'|img(32) }}
                                <a href="{{ subitem.url }}" title="{{ subitem.title }}" class="{{ subitem.visibility != '1' ? 'text-muted': '' }}">{{ subitem.title }}</a>
                                {% if subitem.forum_of_group != 0 %}
                                    <a class="forum-goto" href="../group/group_space.php?{{ _p.web_cid_query }}&gidReq={{ subitem.forum_of_group }}">
                                        {{ "forum.png"|img(22) }} {{ "GoTo"|get_lang }} {{ subitem.forum_group_title }}
                                    </a>
                                {% endif %}
                                {{ subitem.icon_session }}
                                </h3>
                                {% if subitem.last_poster_id is not empty %}
                                    <div class="forum-date">
                                        <i class="fa fa-comments" aria-hidden="true"></i> 
                                        {{ subitem.last_poster_date }} 
                                        {{ "By"|get_lang }} 
                                        {{ subitem.last_poster_user }}
                                    </div>
                                {% endif %}
                                <div class="description">
                                    {{ subitem.description }}
                                </div>
                                {{ subitem.alert }}
                                {% if subitem.moderation is not empty %}
                                    <span class="label label-warning">
                                        {{ "PostsPendingModeration"|get_lang }}: {{ subitem.moderation }}
                                    </span>
                                {% endif %}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        {% endfor %}
{% endfor %}
{% else %}
    <div class="alert alert-warning">
        {{ 'NoForumInThisCategory'|get_lang }}
    </div>
{% endif %}