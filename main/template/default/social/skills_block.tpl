{% import 'default/macro/macro.tpl' as display %}
<script>
$(function() {
    jQuery('.scrollbar-inner').scrollbar();
});
</script>
<div class="panel-group" id="skill-block" role="tablist" aria-multiselectable="true">
    <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingOne">
            <h4 class="panel-title">
                <a role="button" data-toggle="collapse"
                   data-parent="#skill-block" href="#skillList" aria-expanded="true" aria-controls="skillList">
                    {{ "Skills" | get_lang }}
                </a>
                <div class="btn-group pull-right">
                    <a class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown" href="#">
                        <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        {% if show_skills_report_link %}
                            <li>
                                <a href="{{ _p.web_main ~ 'social/my_skills_report.php' }}"> {{'SkillsReport'|get_lang }}</a>
                            </li>
                        {% endif %}
                        <li>
                            <a href="{{ _p.web_main ~ 'social/skills_wheel.php' }}"> {{ 'SkillsWheel'|get_lang }}</a>
                        </li>
                        <li>
                            <a href="{{ _p.web_main ~ 'social/skills_ranking.php' }}"> {{ 'YourSkillRankingX'|get_lang|format(ranking) }}</a>
                        </li>
                    </ul>
                </div>
            </h4>
        </div>
        <div id="skillList" class="panel-collapse collapse in list-{{ orientation }}" role="tabpanel" aria-labelledby="headingOne">
            {% set panel_content %}
                {% if skills %}
                    <div class="list-skills">
                        {% for item in skills %}
                        <div class="item">
                            <div class="image">
                                <a href="{{ item.skill_url }}" title="{{ item.skill_name }}">
                                    {{ item.skill_badge }}
                                </a>
                            </div>
                            <div class="caption">
                                <a href="{{ item.skill_url }}" title="{{ item.skill_name }}">
                                    {{ item.skill_name }}
                                </a>
                            </div>
                        </div>
                        {% endfor %}
                    </div>
                {% else %}
                    <p>{{ 'WithoutAchievedSkills'|get_lang }}</p>
                    <p>
                        <a href="{{ _p.web_main ~ 'social/skills_wheel.php' }}">{{ 'SkillsWheel'|get_lang }}</a>
                    </p>
                {% endif %}
            {% endset %}
            {{ display.panel('', panel_content) }}
        </div>
    </div>
</div>
