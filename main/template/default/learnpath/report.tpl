{{ group_form }}

{{ table }}

<div id="dialog-form" style="display:none;">
    <div class="dialog-form-content">
        {{ 'AreYouSureToDeleteResults' | get_lang | e('html') }} <span id="user_title"></span>
        <div class="form-group">
            <div class="checkbox">
                <label>
                    <input id="delete_exercise_attempts" type="checkbox"> {{ 'DeleteExerciseAttempts' | get_lang }}
                </label>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    $("#dialog-form").dialog({
        autoOpen : false,
        modal : false,
        width : 300,
        height : 250,
        zIndex : 20000
    });

    $('.delete_attempt').on('click', function() {
        var userId = $(this).data('id');
        var username = $(this).data('username');
        $('#user_title').html(username);
        $("#dialog-form").dialog({
            buttons: {
                '{{ "Delete" | get_lang }}' : function() {
                    var deleteExercises = $('#delete_exercise_attempts').prop('checked');
                    var urlDelete = '&delete_exercise_attempts=0';
                    if (deleteExercises) {
                        urlDelete = '&delete_exercise_attempts=1'
                    }
                    window.location.href = "{{ url }}"+urlDelete+"&reset=student&student_id=" + userId;
                }
            },
            close: function() {
                $('#user_title').html('');
                $('#delete_exercise_attempt').prop('checked', false);
            }
        });
        $("#dialog-form").dialog("open");
    });

    $('.delete_all').on('click', function() {
        var users = $(this).data('users');
        $('#user_title').html(users);
        $("#dialog-form").dialog({
            buttons: {
                '{{ "Delete" | get_lang }}' : function() {
                    var deleteExercises = $('#delete_exercise_attempts').prop('checked');
                    var urlDelete = '&delete_exercise_attempts=0';
                    if (deleteExercises) {
                        urlDelete = '&delete_exercise_attempts=1'
                    }
                    window.location.href = "{{ url }}"+urlDelete+"&reset=all&student_id=0";
                }
            },
            close: function() {
                $('#user_title').html('');
                $('#delete_exercise_attempt').prop('checked', false);
            }
        });
        $("#dialog-form").dialog("open");
    });


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
