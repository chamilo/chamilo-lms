{% if profiles is not null %}
          <h4 class="title-skill">{{ "SkillProfiles"|get_lang }}</h4>
        <div class="items_save">
            <ul class="holder_simple">
            {%for profile in profiles %}
            <li class="bit-box">
                <a class="load_profile" rel="{{ profile.id }}" href="#">{{ profile.name }}</a>
            </li>        
            {% endfor %}
            </ul>
        </div>
{% endif %}