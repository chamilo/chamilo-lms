{% for message in messages %}
    {{ message }}
{% endfor %}

{% for description in listing.descriptions %}
    <div id="description_{{description.description_type}}" class="panel panel-default" data-id="{{description.id}}" data-c_id="{{description.c_id}}" data-type="course_description">
        <div class="panel-heading">
            {{ dump(description) }}
            {% if is_allowed_to_edit %}
                <div class="pull-right">
                    {{ dump(session_id) }}

                {% if session_id == description.session_id %}
                    <a href="action=delete&amp;id={{description.id}}"
                       onclick="delete_entry('description_{{description.id}}', this); return false;"
                       title="{{'Delete'|get_lang}}">
                        <em class="size-22 icon-delete"></em>
                    </a>

                    <a href="action=edit&amp;id={{description.id}}"
                       title="{{'Edit'|get_lang}}">
                        <em class="size-22 icon-edit"></em>
                    </a>
                {% else %}
                    <img title="{{'EditionNotAvailableFromSession'|get_lang}}"
                         alt="{{'EditionNotAvailableFromSession'|get_lang}}"
                         src="{{'edit_na.png'|icon(22)}}"  width="22" height="22"
                         style="vertical-align:middle;">
                {% endif %}
                </div>
            {% endif %}

            <img title="{{description.type.title}}" alt="{{description.type.title}}" src="{{description.type.icon|icon(32)}}" class="icon">
            {{description.title}}
        </div>
        <div class="panel-body">
            {{description.content}}
        </div>
    </div>
{% endfor %}
