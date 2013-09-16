{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <script>

        function showSaveButton() {
            var submitStatus = true;

            $(".question_row").each(function() {
                var questionOptionIsChecked = false;
                result = $(this).find("input[type=radio]").each(function() {
                    var isChecked = $(this).is(':checked');
                    if (isChecked == true) {
                        questionOptionIsChecked = true;
                        return;
                    }
                });

                if (questionOptionIsChecked == false) {
                    submitStatus = false;
                    return;
                }
            });

            if (submitStatus) {
                $('#save').show();
            } else {
                $('#save').hide();
            }
        }

        $(document).ready(function() {
            $('.question_row .normal-message').hide();
            showSaveButton();
            $("input[type=radio]").on('click', showSaveButton);
        });

    </script>

    <h3>{{ 'Calificar usuario' |trans }}</h3>
    <form action="{{ url('jury_member.controller:saveScoreAction', {'juryId': jury_id, 'exeId' : exe_id }) }}" method="post">
        {{ exercise }}
        <button id="save" class="btn btn-success btn-large" type="submit">
            {{ 'Save' | trans}}
        </button>
    </form>

{% endblock %}
