{{ form }}

<script>
    $(document).on('ready', function () {
        $('select[name="type"]').on('change', function () {
            var advancedOptionsEl = $('#show_advanced_options');
            var type = parseInt($(this).val());

            if (type > 0) {
                advancedOptionsEl.hide();
            } else {
                advancedOptionsEl.show();
            }
        });
    });
</script>
