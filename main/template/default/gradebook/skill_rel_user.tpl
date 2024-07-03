<div {{ html_content_extra_class }}>
<h3>{{ user.complete_name_with_username }}</h3>
<br />
<script>
    $(function() {
        $(".assign_user_to_skill").on("click", function() {
            var skillId = $(this).attr('data-skill-id');
            var link = $(this);
            $.ajax({
                type: "GET",
                async: false,
                url: "{{ assign_user_url }}&skill_id="+skillId+"&user_id={{ user.id }}&course_id={{ course_id }}&session_id={{ session_id }}",
                success: function(result) {
                    link.removeClass('btn-danger');
                    link.removeClass('btn-success');
                    if (result == 'danger') {
                        link.addClass('btn-danger');
                        link.html('{{ 'NotYetAchieved' | get_lang }}');
                    } else {
                        link.addClass('btn-success');
                        link.html('{{ 'Achieved' | get_lang }}');
                    }
                }
            });
        });
    });
</script>

<table class="table table-striped">
    <tr>
        <th>{{ 'Skill' | get_lang }}</th>
        <th>{{ 'Occurrences' | get_lang }}</th>
        <th>{{ 'Conclusion' | get_lang }}</th>
    </tr>

    {% for skill in skills %}
    <tr>
        <td>{{ skill.name }}</td>
        <td>
            {% for item in items[skill.id] %}
                {% set status = 'danger' %}
                {% if item.info.status %}
                    {% set status = 'success' %}
                {% endif %}
                <span class="label label-{{ status }}">
                    <a href="{{ item.info.url_activity }}" target="_blank">
                        {{ item.info.name }}
                    </a>
                </span>                &nbsp;
            {% endfor %}
        </td>
        <td>
            {% set class = 'danger' %}
            {% set text = 'NotYetAchieved' %}

            {% if conclusion_list[skill.id] %}
                {% set class = 'success' %}
                {% set text = 'Achieved' %}
            {% endif %}

            <a data-skill-id="{{ skill.id }}" href="javascript:void(0);" class="assign_user_to_skill btn btn-{{ class }}">
                {{ text | get_lang }}
            </a>

        </td>
    </tr>
    {% endfor %}
</table>
</div>