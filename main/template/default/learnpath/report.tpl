{{ group_form }}

<div class="table-responsive">
    <table class="table table-hover table-striped table-bordered">
        <thead>
            <tr>
                <th>{{ 'FirstName'|get_lang }}</th>
                <th>{{ 'LastName'|get_lang }}</th>
                {% if show_email %}
                    <th>{{ 'Email'|get_lang }}</th>
                {% endif %}
                <th class="text-center">{{ 'Group'|get_lang }}</th>
                <th class="text-center">{{ 'ScormTime'|get_lang }}</th>
                <th class="text-right">{{ 'Progress'|get_lang }}</th>
                <th class="text-right">{{ 'ScormScore'|get_lang }}</th>
                <th class="text-center">{{ 'LastConnection'|get_lang }}</th>
                {% if not export %}
                <th>{{ 'Actions'|get_lang }}</th>
                {% endif %}
            </tr>
        </thead>
        <tbody>
        {% for user in user_list %}
            {% set trackingUrl = _p.web ~ 'main/mySpace/myStudents.php?details=true' ~ _p.web_cid_query ~ '&course=' ~ course.code ~ '&origin=tracking_course&id_session='~ session_id ~'&student=' ~ user.id %}
            <tr id="row-{{ user.id }}">
                <td>
                    <a href="{{ trackingUrl }}" target="_blank">
                        {{ user.first_name }}
                    </a>
                </td>
                <td>
                    <a href="{{ trackingUrl }}" target="_blank">
                        {{ user.last_name }}
                    </a>
                </td>
                {% if show_email %}
                    <td>{{ user.email }}</td>
                {% endif %}
                <td>{{ user.groups }}</td>
                <td class="text-center">{{ user.lp_time }}</td>
                <td class="text-right">{{ user.lp_progress }}</td>
                <td class="text-right">{{ user.lp_score }}</td>
                <td class="text-center">{{ user.lp_last_connection }}</td>
                {% if not export %}
                <td>
                    <a href="javascript:void(0);" class="details" data-id="{{ user.id }}"><img alt="{{ 'Details' | get_lang }}" src="{{ '2rightarrow.png'|icon(22) }}" /></a>
                    &nbsp;
                    <a
                        href = "{{ url }}&student_id={{ user.id }}&reset=student"
                        onclick = "javascript:if(!confirm('{{ 'AreYouSureToDeleteJS' | get_lang | e('js') }}')) return false;"
                    >
                        <img alt="{{ 'Reset' | get_lang }}" src="{{ 'clean.png'|icon(22) }}" />
                    </a>
                </td>
                {% endif %}
            </tr>
            <tr class="hide"></tr>
        {% endfor %}
        </tbody>
    </table>
</div>

<script>
$(function() {
    $('tr td .details').on('click', function (e) {
        e.preventDefault();
        var self = $(this);
        var userId = self.data('id') || 0;
        var trHead = self.parents('tr');
        var trDetail = trHead.next();
        if (self.is('.active')) {
            self.removeClass('active');
            trDetail.html('').addClass('hide');
        } else {
            self.addClass('active');
            var newTD = $('<td>', {
                colspan: 7
            });
            newTD.load('{{ _p.web_main ~ 'mySpace/lp_tracking.php?action=stats&extend_all=0&id_session=' ~ session_id ~ '&course=' ~ course_code ~ '&lp_id=' ~ lp_id ~ '&student_id=\' + userId + \'&origin=tracking_course&allow_extend=0' }} .table-responsive', function () {
                newTD.appendTo(trDetail);
            });
            trDetail.removeClass('hide');
        }
    });
});
</script>
