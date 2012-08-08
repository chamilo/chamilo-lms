{{ "HelloXAsYouCanSeeYourCourseListIsEmpty" | get_lang | format(_u.complete_name) }}

{% if "allow_students_to_browse_courses" | get_setting  == 'true' %}
    
    {{ "GoAheadAndBrowseOurCourseCatalogXOnceRegisteredYouWillSeeTheCourseHereX" | get_lang | format(course_catalog_link, course_list_link) }}
    
    <a class="btn btn-primary btn-large" href="{{ course_catalog_url }}"> 
        {{ "CourseCatalog" | get_lang }}
    </a>
{% endif %}