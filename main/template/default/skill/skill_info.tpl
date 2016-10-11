<p>{{ "ToGetToLearnXYouWillNeedToTakeOneOfTheFollowingCourses" | get_lang | format( "<em>" ~ skill.name ~ "</em>") }}</p>
<ul>
{% for course in courses %}
    <li>
        <a href="#" class="course_description_popup" rel="{{ course.code }}">
            {{ "SkillXWithCourseX" | get_lang | format(skill.name, course.title) }}
        </a>
    </li>
{% endfor %}

{% for session in sessions %}
    <li>
        {{ "SkillXWithCourseX" | get_lang | format(skill.name, session.name) }}
    </li>
{% endfor %}
</ul>
