{{ form }}
<script>
    $(document).on('ready', function () {
        $('select[name="language"]').on('change', function () {
            location.href += '&language=' + this.value;
        });
    });
</script>
