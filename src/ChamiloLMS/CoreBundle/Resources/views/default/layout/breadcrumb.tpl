{# Breadcrumb #}

<script>
$(document).ready( function() {
    $('.toggle_student_view').on('click', function() {
        $.ajax({
            url: '{{ url('index.controller:toggleStudentViewAction') }}',
            success: function(data) {
                location.reload();
            }
        });
    });
});
</script>

{{ new_breadcrumb }}

{% if ("student_view_enabled" | get_setting) == 'true' %}
    {% if is_granted('ROLE_TEACHER') and app.session.get('_cid')  %}
        <div id="view_as_link" class="pull-right">
            {% if app.session.get('studentview') == 'studentview' %}
                <a class="btn btn-success btn-xs toggle_student_view" target="_self">
                    {{ 'StudentView' | trans }}
                </a>
            {% else %}
                <a class="btn btn-default btn-xs toggle_student_view"target="_self">
                    {{ 'CourseManagerview' | trans }}
                </a>
            {% endif %}
        </div>
    {% endif %}
{% endif %}
