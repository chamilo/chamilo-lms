
<ul id="entries" class="unstyled glossary entries" data-c_id="{{c_id}}" data-session_id="{{session_id}}" >
{% for item in items %}
    <li id="glossary_{{item.id}}" class="glossary term" data-id="{{item.id}}" data-c_id="{{item.c_id}}" data-type="glossary">
        <div class="title sectiontitle">
            {% if is_allowed_to_edit %}
                <div class="pull-right">
                {% if session_id == item.session_id %}
                    <a href="{{root}}&amp;action=edit&amp;id={{item.id}}" 
                       title="{{'Edit'|get_lang}}">
                        <i class="size-22 icon-edit"></i>
                    </a>                    
                    <a href="{{root}}&amp;action=delete&amp;id={{item.id}}" 
                       onclick="ui.remove('glossary_{{item.id}}', this); return false;"
                       title="{{'Delete'|get_lang}}">
                        <i class="size-22 icon-delete"></i>
                    </a>
                
                {% else %}
                    <img title="{{'EditionNotAvailableFromSession'|get_lang}}" 
                         alt="{{'EditionNotAvailableFromSession'|get_lang}}" 
                         src="{{'edit_na.png'|icon(22)}}" 
                         style="vertical-align:middle;">
                {% endif %}
                </div>
            {% endif %}
            
            {{item.name}}
        </div>
        <div class="sectioncomment">
            {{item.description}}
        </div>
    </li>
{% endfor %}
</ul>