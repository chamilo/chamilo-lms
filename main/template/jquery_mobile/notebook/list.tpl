
<ul id="entries" class="notebook entries unstyled" data-c_id="{{c_id}}" data-session_id="{{session_id}}" >
{% for item in items %}
    <li id="notebook_{{item.id}}" class="notebook note" data-id="{{item.id}}" data-c_id="{{item.c_id}}" data-type="notebook">
        <div class="title sectiontitle">
            {% if is_allowed_to_edit %}
                <div class="pull-right element-actions">
                    <a href="{{root}}&amp;action=edit&amp;id={{item.id}}" 
                    title="{{'Edit'|get_lang}}">
                        <i class="size-22 icon-edit"></i>
                    </a>
                    <a href="{{root}}&amp;action=delete&amp;id={{item.id}}" 
                    onclick="ui.remove('notebook_{{item.id}}', this); return false;"
                    title="{{'Delete'|get_lang}}">
                        <i class="size-22 icon-delete"></i>
                    </a>
                </div>
            {% endif %}
            
            {{item.title}}
            
            {% if item.session_id %}
                {{session_image}}
            {% endif %}
        </div>
        <div class="sectioncomment">
            {{item.description}}
        </div>
        <div class="sectionfooter footer">{{item.update_date|date}}</div>
    </li>
{% endfor %}
</ul>