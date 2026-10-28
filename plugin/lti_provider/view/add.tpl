<div class="row">
    <div class="col-sm-9 col-md-offset-3">
        {{ form }}
    </div>
</div>
<script>
  function selectToolProvider(tool) {
    $(".sbox-tool").each(function() {
      if ($(this).hasClass('select2-hidden-accessible')) {
        $(this).select2('destroy');
      }
    });
    $(".sbox-tool").attr('disabled', 'disabled');
    $(".select-tool").hide();
    $("#select-"+tool).show();
    var $select = $("#sbox-tool-"+tool);
    $select.removeAttr('disabled');
    $select.select2({ width: '100%' });
  }
  $(function() {
    if ($("input[name='tool_type']").length > 0) {
      var toolType = $("input[name='tool_type']:checked").val();
      selectToolProvider(toolType);
      $("input[name='tool_type']").on('change', function() {
        selectToolProvider($(this).val());
      });
    }
  });
</script>
