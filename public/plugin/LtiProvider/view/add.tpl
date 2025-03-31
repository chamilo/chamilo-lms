<div class="row">
    <div class="col-sm-9 col-md-offset-3">
        {{ form }}
    </div>
</div>
<script>
  $(function() {
    if ($("input[name='tool_type']").length > 0) {
      var toolType = $("input[name='tool_type']:checked").val();
      selectToolProvider(toolType)
    }
  });
  function selectToolProvider(tool) {
    $(".sbox-tool").attr('disabled', 'disabled');
    $(".select-tool").hide();
    $("#select-"+tool).show();
    $("#sbox-tool-"+tool).removeAttr('disabled');
  }
</script>
