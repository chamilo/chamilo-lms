{# Displayed from exercise_result.php #}

{{ page_top }}
{{ page_content }}
{{ page_bottom }}

{% if allow_signature %}
    <div id="sign_popup" style="display: none">
        <div id="signature_area" class="well">
            <canvas width="400px"></canvas>
        </div>
        <span class="loading" style="display: none"><i class="fas fa-spinner"></i></span>

        <span id="save_controls">
            <button id="sign_popup_save" class="btn btn-primary" type="submit">
                <em class="fa fa-save"></em> {{ 'Save'|get_lang }}
            </button>
            <button id="sign_popup_clean" class="btn btn-default" type="submit">
                <em class="fa fa-eraser"></em> {{ 'Clean'|get_lang }}
            </button>
        </span>
        <span id="close_controls" style="display: none">
            <span id="sign_results"></span>
            <hr />
            <button id="sign_popup_close" class="btn btn-default" type="submit">
                {{ 'Close'|get_lang }}
            </button>
        </span>
    </div>

    <script>
        var imageFormat = 'image/png';
        var canvas = document.querySelector("canvas");
        var signaturePad = new SignaturePad(canvas);
        var dataURL = signaturePad.toDataURL(imageFormat);
        var url = "{{ _p.web_ajax }}exercise.ajax.php?{{ _p.web_cid_query }}";
        var exeId = "{{ exe_id }}";

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

            $("#sign_popup_save").on("click", function() {
                if (signaturePad.isEmpty()) {
                    alert('{{ 'ProvideASignatureFirst'| get_plugin_lang('ExerciseSignaturePlugin') | e('js') }}');
                    return false;
                }

                var dataURL = signaturePad.toDataURL(imageFormat);
                $.ajax({
                    beforeSend: function(result) {
                        $('#loading').show();
                    },
                    type: "POST",
                    url: url,
                    data: "a=sign_attempt&exe_id="+exeId+"&file="+dataURL,
                    success: function(data) {
                        $('#loading').hide();
                        $('#save_controls').hide();
                        $('#close_controls').show();
                        $('#signature_area').hide();

                        signaturePad.clear();
                        if (1 == data) {
                            $('#sign_results').html('{{ 'Saved' | get_lang }}');
                            $('#sign').hide();
                            //$('#sign').html('{{ 'SignatureSaved' | get_lang }}');
                        } else {
                            $('#sign_results').html('{{ 'Error' | get_lang }}');
                        }
                        $('#close_controls').show();
                    },
                });
            });

            $("#sign").on("click", function() {
                $("#sign_popup").dialog({
                    autoOpen: false,
                    width: 500,
                    height: 'auto',
                    //position:  { my: 'left top', at: 'right top'}, //of: $target
                    close: function(){
                        //$("div#"+div_show_id).remove();
                        //$("div#"+div_content_id).remove();
                    }
                });
                $("#sign_popup").dialog("open");
            });
        });
    </script>
{% endif %}