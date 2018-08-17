{% if hot_courses is not null and hot_courses is not empty %}
<section class="hot-courses">
    <div class="page-header">
        <h4>
            {{ "HottestCourses"|get_lang}}
            {% if _u.is_admin %}
            <span class="pull-right">
                <a title="{{ "Hide"|get_lang }}"
                   alt="{{ "Hide"|get_lang }}"
                   href="{{ _p.web_main }}admin/settings.php?search_field=show_hot_courses&submit_button=&_qf__search_settings=&category=search_setting">
                    <i class="fa fa-eye" aria-hidden="true"></i>
                </a>
            </span>
            {% endif %}
        </h4>
    </div>
    <div id="list-hot-courses" class="grid-courses">
        <div class="row">
            {% include 'layout/hot_course_item.tpl'|get_template %}
        </div>
    </div>
</section>
{% endif %}
