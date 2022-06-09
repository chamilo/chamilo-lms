<div id="sign_popup" style="display: none">
    <div id="signature_area" class="well">
        <canvas width="400px"></canvas>
    </div>
    <span id="save_controls">
            <button id="sign_popup_save" class="btn btn-primary" type="submit">
                <em class="fa fa-save"></em> <?php echo get_lang('Save'); ?>
            </button>
            <button id="sign_popup_clean" class="btn btn-default" type="submit">
                <em class="fa fa-eraser"></em> <?php echo get_lang('Clean'); ?>
            </button>
        </span>
    <span id="remove_controls" clase="hidden">
            <button id="sign_popup_remove" class="btn btn-danger" type="submit">
                <em class="fa fa-remove"></em> <?php echo get_lang('Remove'); ?>
            </button>
        </span>
    <span id="close_controls" style="display: none">
            <span id="sign_results"></span>
            <hr />
            <button id="sign_popup_close" class="btn btn-default" type="submit">
                <?php echo get_lang('Close'); ?>
            </button>
        </span>
    <span class="loading" style="display: none"><em class="fa fa-spinner"></em></span>
    <input type="hidden" id="sign-selected" />
</div>

<script>
    var imageFormat = 'image/png';
    var canvas = document.querySelector("#signature_area canvas");
    var signaturePad = new SignaturePad(canvas);
    var urlAjax = "<?php echo api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?'.api_get_cidreq(); ?>";
    var attendance_id = "<?php echo $attendance_id; ?>";

    $(function() {
        $("#sign_popup_close").on("click", function() {
            $("#sign_popup").dialog("close");
            $('#loading').hide();
            $('#save_controls').show();
            $('#close_controls').hide();
            $('#signature_area').show();
        });

        $("#sign_popup_clean").on("click", function() {
            signaturePad.clear();
        });

        $("#sign_popup_remove").on("click", function() {
            var selected = $("#sign-selected").val();
            $.ajax({
                beforeSend: function(result) {
                    $('#loading').show();
                },
                type: "POST",
                url: urlAjax,
                data: "a=remove_attendance_sign&selected="+selected+"&attendance_id="+attendance_id,
                success: function(data) {
                    location.reload();
                },
            });
        });

        $("#sign_popup_save").on("click", function() {
            if (signaturePad.isEmpty()) {
                alert('<?php echo get_lang('ProvideASignatureFirst'); ?>');
                return false;
            }
            var selected = $("#sign-selected").val();
            var dataURL = signaturePad.toDataURL(imageFormat);
            $.ajax({
                beforeSend: function(result) {
                    $('#loading').show();
                },
                type: "POST",
                url: urlAjax,
                data: "a=sign_attendance&selected="+selected+"&file="+dataURL+"&attendance_id="+attendance_id,
                success: function(data) {
                    $('#loading').hide();
                    $('#save_controls').hide();
                    $('#close_controls').show();
                    $('#signature_area').hide();

                    signaturePad.clear();
                    if (1 == data) {
                        $('#sign_results').html('<?php echo get_lang('Saved'); ?>');
                    } else {
                        $('#sign_results').html('<?php echo get_lang('Error'); ?>');
                    }
                    $('#sign_popup_close').hide();
                    location.reload();
                },
            });
        });

        $(".attendance-sign").on("click", function() {
            $("#sign-selected").val($(this).attr("id"));
            $("#sign_popup").dialog({
                autoOpen: false,
                width: 500,
                height: 'auto',
                close: function(){
                }
            });
            $("#sign_popup").dialog("open");
            $("#save_controls").show();
            $("#remove_controls").hide();
            $('#signature_area').show();
            $('#signature_area').html("<canvas width='400px'></canvas>");
            canvas = document.querySelector("#signature_area canvas");
            signaturePad = new SignaturePad(canvas);
        });

        $(".attendance-sign-view").on("click", function() {
            var selected = $(this).attr("id");
            $('#loading').show();
            $.ajax({
                beforeSend: function(result) {
                    $('#signature_area').html("<em class='fa fa-spinner'></em>");
                },
                type: "POST",
                url: urlAjax,
                data: "a=get_attendance_sign&selected="+selected,
                success: function(sign) {
                    $('#loading').hide();
                    $('#signature_area').show();
                    $('#signature_area').html("<img src='"+sign+"' />");
                },
            });
            $("#sign_popup").dialog({
                autoOpen: false,
                width: 500,
                height: 'auto',
                close: function(){
                }
            });
            $("#sign-selected").val(selected);
            $("#sign_popup").dialog("open");
            $("#save_controls").hide();
            $("#remove_controls").show();
        });

        $(".block-calendar").on("click", function(e) {
            e.preventDefault();
            var urlAjax = $(this).attr("href");
            var imgBlocked = $(this).find("img");
            var srcImg =  imgBlocked.attr("src");
            $.ajax({
                type: "POST",
                url: urlAjax,
                success: function(data) {
                    if (1 == data) {
                        var newSrcImg = srcImg.replace("eyes.png", "eyes-close.png");
                        imgBlocked.attr("src", newSrcImg);
                        imgBlocked.attr("title", "<?php echo get_lang('EnableSignature'); ?>");
                    } else {
                        var newSrcImg = srcImg.replace("eyes-close.png", "eyes.png");
                        imgBlocked.attr("src", newSrcImg);
                        imgBlocked.attr("title", "<?php echo get_lang('DisableSignature'); ?>");
                    }
                },
            });
        });

    });

    function searchUser() {
        // Declare variables
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("search-user");
        filter = input.value.toUpperCase();
        table = document.getElementById("table-user-calendar");
        tr = table.getElementsByTagName("tr");

        // Loop through all table rows, and hide those who don\'t match the search query
        for (i = 0; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("td")[1];
            if (td) {
                txtValue = td.textContent || td.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }
</script>
