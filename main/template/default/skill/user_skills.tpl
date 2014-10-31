{% if skills is not empty %}
<ul class="skill-winner">
    {%for skill in skills %}        
        <li>
            <a class="" rel="{{ skill.id}}" href="#">{{ skill.name }}</a>
        </li>        
    {% endfor %}
</ul>    
{% else %}
    {{ 'YouHaveNotYetAchievedSkills' | get_lang }}
{% endif %}