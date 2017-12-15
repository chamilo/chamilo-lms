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
    Webcam.set({
        width: 320,
        height: 240,
        image_format: 'jpeg',
        jpeg_quality: 90
    });
    Webcam.attach('#chamilo-camera');
    //This line Fix a minor bug that made a conflict with a videochat feature in another module file
    $('video').addClass('skip');
</script>
<script language="JavaScript">
    $(document).ready(function () {
        $('#btnCapture').on('click', function (e) {
            e.preventDefault();
            Webcam.freeze();
        });

        $('#btnClean').on('click', function (e) {
            e.preventDefault();
            Webcam.unfreeze();
        });

        $('#btnSave').on('click', function (e) {
            e.preventDefault();
            snap();
        });

        $('#btnAuto').on('click', function (e) {
            e.preventDefault();
            start_video();
        });

        $('#btnStop').on('click', function (e) {
            e.preventDefault();
            stop_video();
        });
    });

    function snap() {
        Webcam.snap(function (data_uri) {
            var clip_filename = '{{ filename }}';
            var url = '{{ _p.web_main }}document/webcam_receiver.php?webcamname=' + escape(clip_filename) + '&webcamdir={{ webcam_dir }}&webcamuserid={{ user_id }}';
            Webcam.upload(data_uri, url, function (code, response) {
                $('#upload_results').html(
                    '<h3>' + response + '</h3>' +
                    '<div>' +
                    '<img src="' + data_uri + '" class="webcamclip_bg">' +
                    '</div>' +
                    '<p hidden="true">' + code + '</p>'
                );
            });
        });
    }
</script>
<script>
    var interval = null;
    var timeout = null;
    var counter = 0;
    var fps = 1000;//one frame per second
    var maxclip = 25;//maximum number of clips
    var maxtime = 60000;//stop after one minute

    function stop_video() {
        interval = window.clearInterval(interval);
        return false;
    }

    function start_video() {
        interval = window.setInterval("clip_send_video()", fps);
    }

    function clip_send_video() {
        counter++;
        timeout = setTimeout('stop_video()', maxtime);
        if (maxclip >= counter) {
            snap();// clip and upload
        }
        else {
            interval = window.clearInterval(interval);
        }
    }
</script>
