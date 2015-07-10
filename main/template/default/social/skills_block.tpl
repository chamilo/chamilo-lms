<div class="panel panel-default social-skill">
    <div class="panel-heading">
        {{ 'Skills'|get_lang }}
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
    </div>
    <div class="panel-body">
        {% if skills %}
            <ul class="list-badges">
                {% for skill in skills %}
                    <li>
                        <img title="{{ skill.name }}" src="{{ skill.icon ? skill.web_icon_thumb_path : 'badges-default.png'|icon(64) }}" width="64" height="64" alt="{{ skill.name }}">
                        <div class="badges-name">{{ skill.name }}</div>
                    </li>
                {% endfor %}
            </ul>
        {% else %}
            <p>{{ 'WithoutAchievedSkills'|get_lang }}</p>
            <p>
                <a href="{{ _p.web_main ~ 'social/skills_wheel.php' }}">{{ 'SkillsWheel'|get_lang }}</a>
            </p>
        {% endif %}
    </div>
</div>
