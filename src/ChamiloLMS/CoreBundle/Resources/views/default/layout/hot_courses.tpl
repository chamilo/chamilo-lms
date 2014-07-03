{% if hot_courses is not null and hot_courses is not empty %}

<script>
$(document).ready( function() {
    $('.star-rating li a').on('click', function(event) {
        var id = $(this).parents('ul').attr('id');
        $('#vote_label2_' + id).html("{{'Loading'|trans}}");
        $.ajax({
            url: $(this).attr('data-link'),
            success: function(data) {
                $("#rating_wrapper_"+id).html(data);
                if (data == 'added') {
                    //$('#vote_label2_' + id).html("{{'Saved'|trans}}");
                }
                if (data == 'updated') {
                    //$('#vote_label2_' + id).html("{{'Saved'|trans}}");
                }
            }
        });
    });
});
</script>
<div id="hot_courses">
    <div class="row">
        <div class="col-md-12">
            {% if _u.is_admin %}
            <span class="pull-right">
                <a
                    title = "{{ "Hide"|trans }}"
                    alt = "{{ "Hide"|trans }}"
                    href="/admin/settings.php?search_field=show_hot_courses&submit_button=&_qf__search_settings=&category=search_setting"
                >
                    <img src="{{ "visible.png"|icon(32) }}">
                </a>
            </span>
            {% endif %}
            <h1>{{ "HottestCourses" | display_page_subheader }}</h1>
        </div>
        {% include '@template_style/layout/hot_course_item.tpl' %}
    </div>
</div>
{% endif %}
