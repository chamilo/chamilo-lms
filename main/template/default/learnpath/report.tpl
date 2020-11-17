{{ group_form }}

{{ table }}

<script>
$(function() {
    $('#group_filter').on('change', function() {
        var groupId  = $(this).val();
        window.location.href = "{{ url_base }}&group_filter=" + groupId;
    });

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
                newTD.insertAfter(trHead);
            });
            trDetail.removeClass('hide');
        }
    });
});
</script>
