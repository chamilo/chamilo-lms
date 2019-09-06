<h2 class="page-header">{{ 'DisplayAccessOverview'|get_lang }}</h2>
{{ form }}
<h3 class="page-header">{{ 'Results'|get_lang }}</h3>
{{ table }}
<script>
    $(function(){
        var courseIdEl = $('#access_overview_course_id'),
            sessionIdEl = $('#access_overview_session_id');

        if (!courseIdEl.val()) {
            sessionIdEl
                .prop('disabled', true)
                .selectpicker('refresh');
        }

        courseIdEl.on('change', function() {
            var self = $(this);

            if (!this.value) {
                sessionIdEl
                    .prop("disabled", true)
                    .selectpicker('refresh');

                return;
            }

            sessionIdEl
                .prop("disabled", false)
                .selectpicker('refresh');
        });
    });
</script>
