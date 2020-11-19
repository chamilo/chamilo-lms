<div class="row">
    <div class="col-sm-6 text-center">
        <h3>{{ 'LocalInputImage'|get_lang }}</h3>
        <div class="webcamclip_bg center-block">
            <div id="chamilo-camera" class="embed-responsive-item"></div>
        </div>
        <form class="text-center">
            <br/>
            <button id="btnCapture" class="btn btn-danger">
                <span class="fa fa-camera" aria-hidden="true"></span>
                {{ 'Snapshot'|get_lang }}
            </button>
            <button id="btnClean" class="btn btn-success">
                <span class="fa fa-refresh" aria-hidden="true"></span>
                {{ 'Clean'|get_lang }}
            </button>
            <button id="btnSave" class="btn btn-primary">
                <span class="fa fa-save" aria-hidden="true"></span>
                {{ 'Save'|get_lang }}
            </button>
            &nbsp;&nbsp;||&nbsp;&nbsp;
            <button id="btnAuto" class="btn btn-default">
                {{ 'Auto'|get_lang }}
            </button>
            <button id="btnStop" class="btn btn-default">
                {{ 'Stop'|get_lang }}
            </button>
        </form>
    </div>
    <div class="col-sm-6">
        <div id="upload_results" class="center-block" style="background-color:#ffffff;"></div>
    </div>
</div>
<script>
    RecordWebcam.init({
        urlReceiver: '{{ _p.web_main }}document/webcam_receiver.php?{{ _p.web_cid_query }}&webcamname='
            + escape('{{ filename }}') + '&webcamdir={{ webcam_dir }}&webcamuserid={{ user_id }}'
    });
</script>
