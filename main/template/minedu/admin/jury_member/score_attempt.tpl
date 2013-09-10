{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <script>

        function showSaveButton() {
            var value = 0;
            var submitStatus = true;

            $("select").each(function() {
                $(this).find('option:checked').each(function() {
                    value = $(this).attr('value');
                    console.log(value);
                    if (value == -1) {
                        submitStatus = false;
                        return;
                    }
                });
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
            $("select").on('change', showSaveButton);
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
