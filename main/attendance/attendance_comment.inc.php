<div id="comment-popup" style="display: none">
    <div id="comment-area" class="well">
        <textarea id="txt-comment" style="width: 100%;height: 150px;" placeholder="<?php echo get_lang('WriteAComment'); ?>"></textarea>
        <div id="comment-author"></div>
    </div>
    <span id="save-comment-controls">
        <span id="comment-results"></span>
        <button id="comment-popup-save" class="btn btn-primary" type="submit">
            <em class="fa fa-save"></em> <?php echo get_lang('Save'); ?>
        </button>
        <button id="comment-popup-close" class="btn btn-default" type="submit">
            <em class="fa fa-eraser"></em> <?php echo get_lang('Close'); ?>
        </button>
    </span>
    <span class="loading" style="display: none"><em class="fa fa-spinner"></em></span>
    <input type="hidden" id="comment-selected" />
</div>
<script>
    var urlAjax = "<?php echo api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?'.api_get_cidreq(); ?>";
    var attendance_id = "<?php echo $attendance_id; ?>";

    $(function() {

        $("#comment-popup-save").on("click", function() {
            var comment = $("#txt-comment").val();
            if (comment == '') {
                alert('<?php echo get_lang('ProvideACommentFirst'); ?>');
                return false;
            }
            var selected = $("#comment-selected").val();
            $.ajax({
                beforeSend: function(result) {
                    $('#loading').show();
                },
                type: "POST",
                url: urlAjax,
                data: "a=comment_attendance&selected="+selected+"&comment="+comment+"&attendance_id="+attendance_id,
                success: function(data) {
                    $('#loading').hide();
                    $('#save-comment-controls').hide();
                    $('#comment-area').hide();
                    if (1 == data) {
                        $('#comment-results').html('<?php echo get_lang('Saved'); ?>');
                    } else {
                        $('#comment-results').html('<?php echo get_lang('Error'); ?>');
                    }
                    $("#comment-popup-close").click();
                },
            });
        });

        $("#comment-popup-close").on("click", function() {
            $("#comment-popup").dialog("close");
            $('#loading').hide();
            $('#save-comment-controls').show();
            $('#comment-area').show();
            $("#txt-comment").val('');
            $("#comment-author").text('');
        });

        $(".attendance-comment").on("click", function() {
            var selected = $(this).attr("id");
            $("#comment-selected").val(selected);
            $("#comment-popup").dialog({
                autoOpen: true,
                width: 500,
                height: 'auto'
            });
            $("#comment-results").hide();
            $("#save-comment-controls").show();
            $('#comment-area').show();
            setComment(selected);
        });

        function setComment(selected) {
            $('#txt-comment').val('...');
            $("#comment-author").text('');
            $.getJSON(urlAjax + "&a=get_attendance_comment&selected=" + selected).done(function (data) {
                if (data.comment) {
                    $('#txt-comment').val(data.comment);
                }
                if (data.author) {
                    $("#comment-author").text(data.author);
                }
            });
        }
    });
</script>
