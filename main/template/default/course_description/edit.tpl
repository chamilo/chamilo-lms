{{javascript}}

{% for message in messages %}
    {{ message }}
{% endfor %}

{% if is_allowed_to_edit %}
    <div class="btn-toolbar actions-bar" >
        <div class="btn-group">
            <a href="{{root}}&amp;action=listing" class="btn" title="{{'ImportCSV'|get_lang}}">
                <i class="size-32 icon-back"></i>
            </a>
        </div>
        <div class="btn-group edit new">
            {% for type in types %}        
                <a href="{{root}}&amp;action=add&amp;description_type={{type.id}}" class="btn "> 
                    <img title="{{type.title}}" alt="{{type.title}}" src="{{type.icon|icon(32)}} ">
                </a>    
            {% endfor %}
        </div>
    </div>
{% endif %}


{% if type.question %}	
    <div class="normal-message">
        <div>            
            <strong>{{'QuestionPlan'|get_lang}}</strong>
        </div>
        {{type.question}}
    </div>
{% endif %}

{{form.return_form()}}