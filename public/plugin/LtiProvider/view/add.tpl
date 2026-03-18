<div class="row">
    <div class="col-sm-9 col-md-offset-3">
        {% if back_url is defined and back_url %}
        <div class="mb-5 flex flex-wrap items-center gap-3">
            <a href="{{ back_url }}" class="btn btn--plain">
                <i class="mdi mdi-arrow-left"></i>
                {{ 'Back'|get_lang }}
            </a>
        </div>
        {% endif %}

        <div class="mt-2">
            {{ form|raw }}
        </div>
    </div>
</div>
<script>
  $(function () {
    if ($("input[name='tool_type']").length > 0) {
      const selectedToolType = $("input[name='tool_type']:checked").val();

      if (selectedToolType) {
        selectToolProvider(selectedToolType);
      }

      $("input[name='tool_type']").on('change', function () {
        selectToolProvider($(this).val());
      });
    }
  });
  function selectToolProvider(tool) {
    $(".sbox-tool").attr('disabled', 'disabled');
    $(".select-tool").hide();
    $("#select-" + tool).show();
    $("#sbox-tool-" + tool).removeAttr('disabled');
  }
</script>
