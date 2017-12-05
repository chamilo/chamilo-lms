{% if allow_skill_tool %}
    <div class="btn-group">
        <a class="btn btn-default" href="{{ _p.web_main }}social/skills_wheel.php">
            {{ 'SkillsWheel' | get_lang }}
        </a>
    </div>
{% endif %}

<style>
    .organigrama * {
        margin: 0px;
        padding: 0px;
    }

    .organigrama ul {
        padding-top: 20px;
        position: relative;
    }

    .organigrama li {
        float: left;
        text-align: center;
        list-style-type: none;
        padding: 20px 5px 0px 5px;
        position: relative;
    }

    .organigrama li::before, .organigrama li::after {
        content: '';
        position: absolute;
        top: 0px;
        right: 50%;
        border-top: 1px solid #f80;
        width: 50%;
        height: 20px;
    }

    .organigrama li::after{
        right: auto;
        left: 50%;
        border-left: 1px solid #f80;
    }

    .organigrama li:only-child::before, .organigrama li:only-child::after {
        display: none;
    }

    .organigrama li:only-child {
        padding-top: 0;
    }

    .organigrama li:first-child::before, .organigrama li:last-child::after{
        border: 0 none;
    }

    .organigrama li:last-child::before{
        border-right: 1px solid #f80;
        -webkit-border-radius: 0 5px 0 0;
        -moz-border-radius: 0 5px 0 0;
        border-radius: 0 5px 0 0;
    }

    .organigrama li:first-child::after{
        border-radius: 5px 0 0 0;
        -webkit-border-radius: 5px 0 0 0;
        -moz-border-radius: 5px 0 0 0;
    }

    .organigrama ul ul::before {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        border-left: 1px solid #f80;
        width: 0;
        height: 20px;
    }

    .organigrama li a {
        border: 1px solid #f80;
        padding: 1em 0.75em;
        text-decoration: none;
        color: #333;
        background-color: rgba(255,255,255,0.5);
        font-family: arial, verdana, tahoma;
        font-size: 0.85em;
        display: inline-block;
        border-radius: 5px;
        -webkit-border-radius: 5px;
        -moz-border-radius: 5px;
        -webkit-transition: all 500ms;
        -moz-transition: all 500ms;
        transition: all 500ms;
    }

    .organigrama li a:hover {
        border: 1px solid #fff;
        color: #ddd;
        background-color: rgba(255,128,0,0.7);
        display: inline-block;
    }

    .organigrama > ul > li > a {
        font-size: 1em;
        font-weight: bold;
    }

    .organigrama > ul > li > ul > li > a {
        width: 8em;
    }

</style>

{% if rows %}
    <h1 class="page-header">{{ 'SkillsAcquired' | get_lang }}</h1>
    <table class="table">
        <thead>
            <tr>
                <th>{{ 'Badge' | get_lang }}</th>
                <th>{{ 'Skill' | get_lang }}</th>
                <th>{{ 'Date' | get_lang }}</th>
                <th>{{ 'Course' | get_lang }}</th>
            </tr>
        </thead>
        <tbody>
        {% for row in rows %}
            <tr>
                <td>{{ row.skill_badge }}</td>
                <td>{{ row.skill_name }}</td>
                <td>{{ row.achieved_at }}</td>
                {% if row.course_name %}
                    <td>
                        <img src="{{ row.course_image }}" alt="{{ row.course_name }}" width="32">
                        {{ row.course_name }}
                    </td>
                {% else %}
                    <td> - </td>
                {% endif %}
            </tr>
        {% endfor %}
        </tbody>
    </table>
    {% if skill_table %}
        {{ skill_table }}
    {% endif %}
{% endif %}
