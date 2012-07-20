{{javascript}}

{% for message in messages %}
    {{ message }}
{% endfor %}

<div class="actions" >
    <a href = "{{root}}&amp;action=listing" class = "course_description btn back"></a>
    {% for type in types %}        
        <a href="{{root}}&amp;action=add&amp;description_type={{type.id}}"> 
            <img title="{{type.title}}" alt="type.title" src="{{type.icon|icon(32)}} ">
        </a>    
    {% endfor %}
</div>

{% if type.question %}	
    <div class="normal-message">
        <div>            
            <strong>{{'QuestionPlan'|get_lang}}</strong>
        </div>
        {{type.question}}
    </div>
{% endif %}

{{form.return_form()}}