<div class="normal-message">
  {{ "HelloXAsYouCanSeeYourCourseListIsEmpty"|get_lang | format(_u.complete_name) }}

{% if count_courses  == 0 %}
    {{ "PleaseAllowUsALittleTimeToSubscribeYouToOneOfOurCourses"|get_lang }}
{% else %}
    {% if not 'hide_course_catalog_welcome'|api_get_configuration_value %}
        {% if "allow_students_to_browse_courses"|api_get_setting  == 'true' %}
            {{ "GoAheadAndBrowseOurCourseCatalogXOnceRegisteredYouWillSeeTheCourseHereX"|get_lang|format(course_catalog_link, course_list_link) }}
            <a class="btn btn-primary btn-large" href="{{ course_catalog_url }}">
                {{ "CourseCatalog"|get_lang }}
            </a>
        {% else %}
            {{ "PleaseAllowUsALittleTimeToSubscribeYouToOneOfOurCourses"|get_lang }}
        {% endif %}
    {% endif %}
{% endif %}
</div>