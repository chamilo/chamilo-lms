{% if search_skill_list is not null %}
    <div class="skills-skills">
        <h3>{{"Skills"|get_lang}}</h3>
        <ul class="holder">             
            {% for search_skill_id in search_skill_list %}        
                <li class="bit-box">
                    {{ skill_list[search_skill_id].name}}
                    <a class="closebutton" href="?a=remove_skill&id={{search_skill_id}}"></a>
                </li>        
            {% endfor %}
        </ul>
        <a id="add_profile" class="btn" href="#"> {{"SaveThisSearch"|get_lang}}</a>
    </div>
{% endif %}

{% if profiles is not null %}    
    <div class="skills-profiles">
        <h3>{{"SkillProfiles"|get_lang}}</h3>
        <ul class="holder">
            {%for profile in profiles %}        
                <li class="bit-box">
                    <a href="?a=load_profile&id={{profile.id}}">{{profile.name}}</a>
                </li>        
            {% endfor %}
        </ul>    
    </div>
{% endif %}

{% if order_user_list is not null %}
    {% for count, user_list in order_user_list %}
<div class="page-items-profile">
        <div class="row-fluid">
            <div class="span12">
                <h4 class="title-skill">
                    {% if count == total_search_skills %}
                        {{ "CompleteMatch"|get_lang }}
                    {% else %}
                    {% if (total_search_skills - count) == 1 %}
                        {{ "MissingOneStepToMatch"|get_lang }}
                    {% else %}
                        {{ "MissingXStepsToMatch"|get_lang | format(total_search_skills - count)}}                        
                    {% endif %}
            {% endif %}
                </h4>
            </div>
        </div>

        <div class="row-fluid">
        {% for user in user_list %}
            <div class="block-items">
                <div class="border-items">
                <div class="items-info">
                <div class="avatar-profile">
                    <img width="96px" src="{{user.user.avatar}}" />
                </div>
                <div class="info-profile">
                    <h4><a href="{{ _p.web_main }}social/profile.php?u={{ user['user'].user_id }}">{{ user['user'].complete_name }} </a></h4>
                    <p>Usuario:{{user['user'].username}} </p>
                </div>
                <div class="number-skill">{{ "Skills"|get_lang }} {{ user.total_found_skills }} / {{ total_search_skills }}</div>
                <div class="skill-user-items">
                    <ul class="award-items">
                        {% for skill_data in user.skills %}
                        <li>
                            {% if skill_list[skill_data.skill_id].name is not null %}
                            {% if skill_data.found %}
                            <img src="{{ _p.web }}main/img/icons/22/award_red_start.png" />{{ skill_list[skill_data.skill_id].name }}
                            {% else %}
                            <img src="{{ _p.web }}main/img/icons/22/award_green.png" />{{ skill_list[skill_data.skill_id].name }}
                            {% endif %}

                            {% else %}
                            {{ "SkillNotFound"|get_lang }}
                            {% endif %}
                                {# if $skill_data.found
                                "IHaveThisSkill"|get_lang
                                #}
                                </li>
                            {% endfor %}
                        </ul>
                </div>
                </div>
            </div>
            </div>
        {% endfor %}
    {% endfor %}
    </div>

    {% else %}
    {% if search_skill_list is null %}
        <div class="warning-message">{{"NoResults"|get_lang}}</div>
    {% endif %}
    {% endif %}
</div>
<div id="dialog-form" style="display:none;">    
    <form id="save_profile_form" class="form-horizontal" name="form">       
        <fieldset>
            <div class="control-group">            
                <label class="control-label" for="name">{{"Name"|get_lang}}</label>            
                <div class="controls">
                    <input type="text" name="name" id="name" size="40" />             
                </div>
            </div>        
            <div class="control-group">            
                <label class="control-label" for="name">{{"Description"|get_lang}}</label>            
                <div class="controls">
                    <textarea name="description" id="description" class="span2"  rows="7"></textarea>
                </div>
            </div>  
        </fieldset>
    </form>    
</div>
