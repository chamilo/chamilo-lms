<div class="table-responsive">
    <table class="table table-hover table-striped table-bordered">
        <thead>
            <tr>
                <th>{{ 'FirstName'|get_lang }}</th>
                <th>{{ 'LastName'|get_lang }}</th>
                {% if show_email %}
                    <th>{{ 'Email'|get_lang }}</th>
                {% endif %}
                <th class="text-center">{{ 'ScormTime'|get_lang }}</th>
                <th class="text-right">{{ 'Progress'|get_lang }}</th>
                <th class="text-right">{{ 'ScormScore'|get_lang }}</th>
                <th class="text-center">{{ 'LastConnection'|get_lang }}</th>
                <th>{{ 'Actions'|get_lang }}</th>
            </tr>
        </thead>
        <tbody>
            {% for user in user_list %}
                <tr id="row-{{ user.id }}">
                    <td>{{ user.first_name }}</td>
                    <td>{{ user.last_name }}</td>
                    {% if show_email %}
                        <td>{{ user.email }}</td>
                    {% endif %}
                    <td class="text-center">{{ user.lp_time }}</td>
                    <td class="text-right">{{ user.lp_progress }}</td>
                    <td class="text-right">{{ user.lp_score }}</td>
                    <td class="text-center">{{ user.lp_last_connection }}</td>
                    <td>
                        <button class="btn btn-primary btn-sm" data-id="{{ user.id }}">{{ 'Details'|get_lang }}</button>
                    </td>
                </tr>
                <tr class="hide"></tr>
            {% endfor %}
        </tbody>
    </table>
</div>

<script>
$(document).on('ready', function () {
    $('tr td button').on('click', function (e) {
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
