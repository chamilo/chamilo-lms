<?php
/* For licensing terms, see /license.txt */

/**
 * Input file with progress element.
 *
 * Class BigUpload
 */
class BigUpload extends HTML_QuickForm_file
{
    /**
     * @param string $elementName
     * @param string $elementLabel
     * @param array  $attributes
     */
    public function __construct($elementName = null, $elementLabel = null, $attributes = null)
    {
        parent::__construct($elementName, $elementLabel, $attributes);
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        $origin = $this->getAttribute('data-origin');
        $id = $this->getAttribute('id');
        $maxSize = getIniMaxFileSizeInBytes();
        $errorUploadMessage = get_lang('FileSizeIsTooBig').' '.get_lang('MaxFileSize').' : '.getIniMaxFileSizeInBytes(true);
        $html = parent::toHtml();
        $html .= '<div id="'.$id.'-bigUploadProgressBarContainer">
            <div id="'.$id.'-bigUploadProgressBarFilled"></div>
        </div>
        <div id="'.$id.'-bigUploadTimeRemaining"></div>
        <div id="'.$id.'-bigUploadResponse"></div>';
        $js = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'bigupload/js/bigUpload.js"></script>';
        $js .= '<script>
            var bigUpload = new bigUpload();
            var uploadForm, formId, submitButtonId;
            $(function() {
                uploadForm = $("#'.$id.'").closest("form");
                formId = uploadForm.attr("id");
                submitButtonId = uploadForm.find("[type=\'submit\']").attr("id");
                $("#"+submitButtonId).click(function(e) {
                    if ($("#'.$id.'").val()) {
                        e.preventDefault();
                        setBigUploadSettings();
                        bigUpload.fire();
                    }
                });
            });
            function setBigUploadSettings() {
                //The id of the file input
                bigUpload.settings.inputField = "'.$id.'";
                //The id of the form with the file upload.
                bigUpload.settings.formId = formId;
                //The id of the progress bar
                bigUpload.settings.progressBarField = "'.$id.'-bigUploadProgressBarFilled";
                //The id of the time remaining field
                bigUpload.settings.timeRemainingField = "'.$id.'-bigUploadTimeRemaining";
                //The id of the text response field
                bigUpload.settings.responseField = "'.$id.'-bigUploadResponse";
                //The id of the submit button
                bigUpload.settings.submitButton = submitButtonId;
                //Color of the background of the progress bar
                bigUpload.settings.progressBarColor = "#5bb75b";
                //Color of the background of the progress bar when an error is triggered
                bigUpload.settings.progressBarColorError = "#da4f49";
                //Path to the php script for handling the uploads
                bigUpload.settings.scriptPath = "'.api_get_path(WEB_LIBRARY_JS_PATH).'bigupload/inc/bigUpload.php";
                //cid Req
                bigUpload.settings.cidReq = "'.api_get_cidreq().'";
                //Set the origin upload
                bigUpload.settings.origin = "'.$origin.'";
                //The parameters from the upload form
                bigUpload.settings.formParams = uploadForm.serialize();
                //Max file size allowed
                bigUpload.settings.maxFileSize = "'.$maxSize.'";
                // Message error upload filesize
                bigUpload.settings.errMessageFileSize = "'.$errorUploadMessage.'";
            }
        </script>';

        return $js.$html;
    }
}
