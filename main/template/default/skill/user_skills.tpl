{% if skills is not null %}
<ul class="skill-winner">
    {%for skill in skills %}        
        <li>
            <a class="" rel="{{ skill.id}}" href="#">{{ skill.name }}</a>
        </li>        
    {% endfor %}
</ul>    
{% endif %}