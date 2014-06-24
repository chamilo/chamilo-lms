{{ "HelloXAsYouCanSeeYourCourseListIsEmpty"|trans | format(_u.complete_name) }}

{% if count_courses  == 0 %}
    {{ "PleaseAllowUsALittleTimeToSubscribeYouToOneOfOurCourses"|trans }}
{% else %}

    {% if "allow_students_to_browse_courses"|get_setting  == 'true' %}

        {{ "GoAheadAndBrowseOurCourseCatalogXOnceRegisteredYouWillSeeTheCourseHereX"|trans|format(course_catalog_link, course_list_link) }}

        <a class="btn btn-primary btn-large" href="{{ course_catalog_url }}">
            {{ "CourseCatalog"|trans }}
        </a>
    {% else %}
        {{ "PleaseAllowUsALittleTimeToSubscribeYouToOneOfOurCourses"|trans }}
    {% endif %}

{% endif %}
