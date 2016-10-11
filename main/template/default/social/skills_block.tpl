<script>
jQuery(document).ready(function(){
    jQuery('.scrollbar-inner').scrollbar();
});
</script>
<div class="panel-group" id="skill-block" role="tablist" aria-multiselectable="true">
    <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingOne">
            <h4 class="panel-title">
                <a role="button" data-toggle="collapse" data-parent="#skill-block" href="#skillList" aria-expanded="true" aria-controls="skillList">
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
        <div id="skillList" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
            <div class="panel-body">
                {% if skills %}
                    <div class="scrollbar-inner badges-sidebar">
                        <ul class="list-unstyled list-badges">
                            {% for skill in skills %}
                                <li class="thumbnail">
                                    <a href="{{ _p.web }}skill/{{ skill.id }}/user/{{ user_id }}" target="_blank">
                                        <img title="{{ skill.name }}" class="img-responsive" src="{{ skill.icon ? skill.web_icon_thumb_path : 'badges-default.png'|icon(64) }}" width="64" height="64" alt="{{ skill.name }}">
                                        <div class="caption">
                                            <p class="text-center">{{ skill.name }}</p>
                                        </div>
                                    </a>
                                </li>
                            {% endfor %}
                        </ul>
                    </div>
                {% else %}
                    <p>{{ 'WithoutAchievedSkills'|get_lang }}</p>
                    <p>
                        <a href="{{ _p.web_main ~ 'social/skills_wheel.php' }}">{{ 'SkillsWheel'|get_lang }}</a>
                    </p>
                {% endif %}
            </div>
        </div>
    </div>
</div>