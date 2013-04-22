{% if profiles is not null %}
    <div class="page-header">
        <h3>{{ "SkillProfiles"|get_lang }}</h3>
    </div>
    <ul class="holder">
        {%for profile in profiles %}        
            <li class="bit-box">
                <a class="load_profile" rel="{{ profile.id }}" href="#">{{ profile.name }}</a>
            </li>        
        {% endfor %}
    </ul>    
{% endif %}