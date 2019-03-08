{% extends 'layout/layout_1_col.tpl'|get_template %}
{% block content %}

<script>
    $(document).ready(function () {
        // default
        $('.category-forum ').hide();
        $('.{{ default_user_language }}').show();
        $('#extra_language').attr('data-width', '200px');
        $('#extra_language option[value=""]').text('{{ 'Any' | get_lang | escape('js') }}');
        $('#extra_language').on('change', function() {
            var selectedLanguageArray = $(this).val();
            $('.category-forum ').hide();
            $.each(selectedLanguageArray, function(index, selectedLanguage) {
                if (selectedLanguage == '') {
                    $('.category-forum ').show();
                } else {
                    $('.'+ selectedLanguage).show();
                }
            });
        });
    });
</script>

{{ form_content }}
{{ search_filter }}

{% if data is not empty %}
    {% for item in data %}
        {% set category_language_array = [] %}
        {% set category_language = '' %}
        {% for extra_field in item.extra_fields %}
            {% if extra_field.variable == 'language' %}
                {% set category_language_array = extra_field.value | split(';')  %}
                {% set category_language = extra_field.value | replace({';': ' ' })  %}
            {% endif %}
        {% endfor %}

        <div class="category-forum {{ category_language }}" id="category_{{ item.id }}">
            <div class="category-title">
                <h3>
                    {{ 'forum_blue.png'|img(32) }}
                    <a href="{{ item.url }}" title="{{ item.title }}">{{ item.title }}{{ item.icon_session }}</a>
                    {% for category_language_item in category_language_array %}
                        <span class="flag-icon flag-icon-{{ languages[category_language_item | lower] }}"></span>
                    {% endfor %}
                </h3>
                <div class="tools">
                    {{ item.tools }}
                </div>
            </div>
            <div class="forum-description">
                {{ item.description }}
            </div>
            {% for subitem in item.forums %}
                <div class="forum_display">
                    <div class="panel panel-default forum">
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-xs-4 col-md-3">
                                    <div class="number-post">
                                        <a href="{{ subitem.url }}" title="{{subitem.title}}">

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
                                <div class="col-xs-8 col-md-9">

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
                                            « {{ subitem.last_post_title }} »
                                            {{ "By"|get_lang }}
                                            {{ subitem.last_poster_user }}
                                        </div>
                                    {% endif %}
                                    <div class="description">
                                        {{ subitem.description }}
                                    </div>

                                    {{ subitem.last_post_text }}

                                    {{ subitem.alert }}
                                    {% if subitem.moderation is not empty %}
                                        <span class="label label-warning">
                                            {{ "PostsPendingModeration"|get_lang }}: {{ subitem.moderation }}
                                        </span>
                                    {% endif %}

                                    <div class="tools">
                                        <div class="toolbar">
                                            {{ subitem.tools }}
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            {% endfor %}
        </div>
    {% endfor %}
    {% else %}
        <div class="alert alert-warning">
            {{ 'NoForumInThisCategory'|get_lang }}
        </div>
    {% endif %}
{% endblock %}
