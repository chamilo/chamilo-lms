{% if search_skill_list is not null %}
    <div class="skills-skills">
        <h3>{{"Skills"|trans}}</h3>
        <ul class="holder">
            {% for search_skill_id in search_skill_list %}
                <li class="bit-box">
                    {{ skill_list[search_skill_id].name}}
                    <a class="closebutton" href="?a=remove_skill&id={{search_skill_id}}"></a>
                </li>
            {% endfor %}
        </ul>
        <a id="add_profile" class="btn" href="#"> {{"SaveThisSearch"|trans}}</a>
    </div>
{% endif %}

{% if profiles is not null %}
    <div class="skills-profiles">
        <h3>{{"SkillProfiles"|trans}}</h3>
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
        <div class="row-fluid">
            <div class="span12">
                <div class="page-header">
                    <h2>
                    {% if count == total_search_skills %}
                        {{ "CompleteMatch"|trans }}
                    {% else %}
                        {% if (total_search_skills - count) == 1 %}
                            {{ "MissingOneStepToMatch"|trans }}</h2>
                        {% else %}
                            {{ "MissingXStepsToMatch"|trans | format(total_search_skills - count)}}
                        {% endif %}
                    {% endif %}
                    </h2>
                </div>
            </div>
        </div>

        <div class="row">
        {% for user in user_list %}
            <div class="span4">
                <div class="well_border">
                    <h3>
                        <img src="{{user.user.avatar_small}}" />
                        <a href="{{ user['user'].profile_url }}">{{ user['user'].complete_name }} ({{user['user'].username}}) </a>
                    </h3>
                    <hr >
                    <div class="ui-widget-content ">
                        <h4>{{ "Skills"|trans }} {{ user.total_found_skills }} / {{ total_search_skills }}</h4>
                        <ul>
                            {% for skill_data in user.skills %}
                                <li>
                                    {% if skill_list[skill_data.skill_id].name is not null %}
                                        {% if skill_data.found %}
                                            <span class="label label-important">{{ skill_list[skill_data.skill_id].name }}</span>
                                        {% else %}
                                            <span class="label_tag skill">{{ skill_list[skill_data.skill_id].name }}</span>
                                        {% endif %}

                                    {% else %}
                                        {{ "SkillNotFound"|trans }}
                                    {% endif %}
                                    {# if $skill_data.found
                                         IHaveThisSkill"|trans
                                    #}
                                </li>
                            {% endfor %}
                        </ul>
                    </div>
                </div>
            </div>
        {% endfor %}
    {% endfor %}
    </div>
{% else %}
    {% if search_skill_list is null %}
        <div class="warning-message">{{"NoResults"|trans}}</div>
     {% endif %}
{% endif %}

<div id="dialog-form" style="display:none;">
    <form id="save_profile_form" class="form-horizontal" name="form">
        <fieldset>
            <div class="control-group">
                <label class="control-label" for="name">{{"Name"|trans}}</label>
                <div class="controls">
                    <input type="text" name="name" id="name" size="40" />
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="name">{{"Description"|trans}}</label>
                <div class="controls">
                    <textarea name="description" id="description" class="span2"  rows="7"></textarea>
                </div>
            </div>
        </fieldset>
    </form>
</div>
