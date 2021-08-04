{% extends 'layout/layout_1_col.tpl'|get_template %}

{% block content %}
    {{ content }}
    <style>
        .input-group .form-control {
            z-index: auto !important;
        }
        /* WIP: To be move in base.css */
        .search-courses .form-inline-box .input-group {
            width: 80%;
            padding-bottom: 14px;
            z-index: auto;
        }
        .search-courses .form-inline-box .input-group label {
            margin-bottom: 0px;
            z-index: auto;
        }
    </style>
<div>
    {{ 'TotalNumberOfAvailableCourses'|get_lang }} :
    <strong>{{ total_number_of_courses }}</strong>
</div>
<div>
    {{ 'NumberOfMatchingCourses'|get_lang }} :
    <strong>{{ total_number_of_matching_courses }}</strong>
</div>
    {% block course_grid %}
        <div class="grid-courses row">
            {% for course in courses %}
                <div class="col-xs-12 col-sm-6 col-md-4">
                    <div class="items items-courses">
                        {% include 'catalog/course_item_catalog.tpl'|get_template %}
                    </div>
                </div>
            {% endfor %}
        </div>
    {% endblock %}
    <div class="col-md-12">
        {{ pagination }}
    </div>
{% endblock %}
