{% if hot_courses is not null and hot_courses is not empty %}

<script>
$(document).ready( function() {
    $('.star-rating li a').live('click', function(event) {
        var id = $(this).parents('ul').attr('id');
        $('#vote_label2_' + id).html("{{'Loading'|get_lang}}");
        $.ajax({
            url: $(this).attr('data-link'),
            success: function(data) {
                $("#rating_wrapper_"+id).html(data);
                if (data == 'added') {
                    //$('#vote_label2_' + id).html("{{'Saved'|get_lang}}");
                }
                if (data == 'updated') {
                    //$('#vote_label2_' + id).html("{{'Saved'|get_lang}}");
                }
            }
        });
    });

});
</script>

    <section id="hot_courses">
        <div class="row">
            <div class="span9">
                {% if _u.is_admin %}
                <span class="pull-right">
                    <a title="{{ "Hide"|get_lang }}" alt="{{ "Hide"|get_lang }}" href="{{ _p.web_main }}admin/settings.php?search_field=show_hot_courses&submit_button=&_qf__search_settings=&category=search_setting">
                        <img src="{{ "visible.png"|icon(32) }}">
                    </a>
                </span>
                {% endif %}
                {{ "HottestCourses"|display_page_header }}
            </div>            
                
            {% include 'default/layout/hot_course_item.tpl' %}
        </div>
    </section>
{% endif %}
