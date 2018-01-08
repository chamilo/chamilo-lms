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
        <a id="add_profile" class="btn btn-default" href="#">
            <em class="fa fa-floppy-o"></em> {{"SaveThisSearch"|get_lang}}
        </a>
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
        <div class="row">
            <div class="col-md-12">
                <h4 class="page-header">
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

        <div class="row">
            {% for user in user_list %}
                <div class="col-md-3">
                    <div class="items-user">
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <div class="avatar-user">
                                <img class="img-circle" width="100" src="{{ user.user.avatar }}" class="center-block">
                            </div>
                            <p class="text-center"><em class="fa fa-user"></em>
                                <a href="{{ _p.web_main }}social/profile.php?u={{ user['user'].user_id }}" target="_blank">
                                    {{ user['user'].complete_name_with_username }}
                                </a>
                            </p>
                            <p class="text-center">
                                <em class="fa fa-graduation-cap"></em> {{ "AchievedSkills"|get_lang }} {{ user.total_found_skills }} / {{ total_search_skills }}
                            </p>
                        </div>
                        <ul class="list-group">
                            {% for skill_data in user.skills %}
                                <li class="list-group-item {{ skill_data.found ? '' : 'text-muted' }}">
                                    {% if skill_list[skill_data.skill_id].icon %}
                                        <img src="{{ _p.web_upload ~ skill_list[skill_data.skill_id].icon_small }}" width="22" height="22" alt="{{ skill_list[skill_data.skill_id].name }}">
                                    {% else %}
                                        <img src="{{ 'badges.png'|icon(22) }}" width="22" height="22" alt="{{ skill_list[skill_data.skill_id].name }}">
                                    {% endif %}

                                    {% if skill_data.found %}
                                        <b>{{ skill_list[skill_data.skill_id].name }}</b>
                                    {% else %}
                                        {{ skill_list[skill_data.skill_id].name }}
                                    {% endif %}
                                </li>
                            {% endfor %}
                        </ul>
                    </div>
                    </div>
                </div>
            {% endfor %}
        </div>
    {% endfor %}
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
