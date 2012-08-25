{% if skills is not null %}
<ul class="nav nav-list">
    {%for skill in skills %}        
        <li>
            <a rel="{{ skill.id}}" href="#">
                <span class="label label-info">
            {{ skill.name }}
                </span>
                </a>
        </li>        
    {% endfor %}
</ul>    
{% endif %}