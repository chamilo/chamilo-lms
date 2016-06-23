{{ form }}
<script>
    $(document).on('ready', function () {
        $('select[name="sub_language"]').on('change', function () {
            location.href += '&sub_language=' + this.value;
        });
    });
</script>
