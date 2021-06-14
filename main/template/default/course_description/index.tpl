{% for message in messages %}
    {{ message }}
{% endfor %}
{% for description in listing.descriptions %}
    {% if not description is empty %}
        <div id="description_{{ description.description_type }}" class="panel panel-default"
             data-id="{{ description.id }}" data-c_id="{{ description.c_id }}" data-type="course_description">
            <div class="panel-heading">
                {% if is_allowed_to_edit %}
                    <div class="pull-right">
                        {% if session_id == description.session_id %}
                            <a href="{{ _p.web_self }}?action=edit&id={{ description.id }}&{{ _p.web_cid_query }}"
                               title="{{ 'Edit'|get_lang }}">
                                <img src="{{ 'edit.png'|icon(22) }}"/>
                            </a>
                            <a href="{{ _p.web_self }}?action=delete&id={{ description.id }}&{{ _p.web_cid_query }}"
                               onclick = "javascript:return confirmation('{{ description.title_js | e('js') }}');"
                               title="{{ 'Delete'|get_lang }}">
                                <img src="{{ 'delete.png'|icon(22) }}"/>
                            </a>
                        {% else %}
                            <img title="{{ 'EditionNotAvailableFromSession'|get_lang }}"
                                 alt="{{ 'EditionNotAvailableFromSession'|get_lang }}"
                                 src="{{ 'edit_na.png'|icon(22) }}" width="22" height="22"
                                 style="vertical-align:middle;">
                        {% endif %}
                    </div>
                {% endif %}
                {{ description.title | remove_xss }}
            </div>
            <div class="panel-body">
                {{ description.content | remove_xss }}
            </div>
        </div>
    {% endif %}
{% endfor %}
