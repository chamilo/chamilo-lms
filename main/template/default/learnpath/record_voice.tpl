{# <script type="text/javascript" src="{{ _p.web_lib }}javascript/rtc/RecordRTC.js"></script> #}
<script type="text/javascript" src="{{ _p.web_lib }}swfobject/swfobject.js"></script>
<script type="text/javascript" src="{{ _p.web_lib }}wami-recorder/recorder.js"></script>
<script type="text/javascript" src="{{ _p.web_lib }}wami-recorder/gui.js"></script>

<div id="rtc">
    <audio class="skip" id="audio" autoplay="" loop="" controls=""></audio>
    <span id="progress-info"></span>
    <br />
    <a id="record" class="btn btn-danger" >{{ 'RecordAudio' | get_lang }}</a>
    <button id="stop" class="btn" disabled="">{{ 'StopRecordingAudio' | get_lang }}</button>
</div>

<div id="wami" style="float:left; z-index:6000">
    <a id="record-wami" class="btn btn-info">{{ 'ActivateAudioRecorder' | get_lang }}</a>
    <br />
    <div id="wami-recorder">
    </div>
    <div id="start-recording" class="alert" style="display:none">
        {{ "StartSpeaking" | get_lang }}
    </div>
</div>

<div id="nanogong">
    <applet id="nanogongApplet" archive="{{ _p.web_lib }}nanogong/nanogong.jar" code="gong.NanoGong" width="250" height="95">
        <param name="ShowTime" value="true" />
        <param name="AudioFormat" value="ImaADPCM" />
        <param name="ShowSpeedButton" value="false" />
    </applet>

    <form name="form_nanogong">
        <input id="status" type="hidden" name="status" value="0">
        <button class="upload" type="submit" value="{{ 'Send' | get_lang }}" onclick="submitVoice()">
            {{ 'Send' | get_lang }}
        </button>
    </form>
    <span id="nanogong_result" ></span>
</div>

<span id="record-result"></span>
<div class="clear"></div>

<script>

function checkNanoGongEnable() {
    var nanogongEnabled = {{ enable_nanogong }};
    if (nanogongEnabled != 1) {
        return false;
    }
    return recorder = document.getElementById("nanogongApplet");
}

function checkWamiEnable() {
    var wamiEnabled = {{ enable_wami }};
    if (wamiEnabled != 1) {
        return false;
    }

    if (swfobject.hasFlashPlayerVersion("1")) {
        return true;
    } else {
        return false;
    }
}

function submitVoice() {
    // lang vars
    var lang_no_applet = "{{ 'NanogongNoApplet' | get_lang }}";
    var lang_record_before_save = "{{ 'NanogongRecordBeforeSave' | get_lang }}";
    var lang_give_a_title = "{{ 'NanogongGiveTitle' | get_lang }}";
    var lang_failled_to_submit = "{{ 'NanogongFailledToSubmit' | get_lang }}";
    var lang_submitted = "{{'NanogongSubmitted' | get_lang }}";
    // user and group id

    //path, url and filename
    var filename = "{{ filename }}"; //adding name file, tag and extension
    var filename = encodeURIComponent(filename);
    var fileInput = 'file';
    var urlNanoGong = "{{ _p.web_lib }}nanogong/upload_nanogong_file.php?lp_item_id={{ lp_item_id }}&filename="+filename+"&file_field="+fileInput+"&cidReq={{ course_code }}&path={{ cur_dir_path }}";
    var cookie= "ch_sid=" + "{{ php_session_id }}";

    // Check
    var recorder;

    if (!(recorder = document.getElementById("nanogongApplet"))) {
        alert(lang_no_applet);
        return
    }

    var duration = parseInt(recorder.sendGongRequest("GetMediaDuration", "audio")) || 0
    if (duration <= 0) {
        alert(lang_record_before_save);
        return;
    }

    var ret = recorder.sendGongRequest("PostToForm", urlNanoGong, fileInput, cookie, filename);

    if (ret == null)  {
        alert(lang_failled_to_submit);
    } else {
        alert(lang_submitted);
        $("#status").attr('value', '1');
    }
}

function setupRecorder() {
    $('#wami-recorder').css('margin-bottom', '150px');
    Wami.setup({
        id : "wami",
        onReady : setupGUI,
        swfUrl : "{{ _p.web_lib }}wami-recorder/Wami.swf"
    });
}

function setupGUI() {
    var uniq = 'rec_' + (new Date()).getTime() + ".wav";
    var gui = new Wami.GUI({
        id : "wami-recorder",
        singleButton : true,
        recordUrl : "{{ _p.web_lib }}wami-recorder/record_document.php?lp_item_id={{ lp_item_id }}&waminame="+uniq+"&wamidir={{ cur_dir_path }}&wamiuserid={{ _u.user_id }}",
        buttonUrl : "{{ _p.web_lib }}wami-recorder/buttons.png",
        buttonNoUrl : "{{ _p.web_img }}blank.gif",
        onRecordStart : function() {
            $('#start-recording').show();
        },
        onRecordFinish: function() {
            $('#start-recording').hide();
            window.location.reload();
        },
        onError : function() {
        }
    });

    gui.setPlayEnabled(true);
}

var rtcEnabled = false;

$(document).ready(function() {
    var isChrome = navigator.webkitGetUserMedia;

    $('#rtc').hide();
    $('#wami').hide();
    $('#nanogong').hide();

    if (rtcEnabled && isChrome) {
        $('#rtc').show();
    } else if (checkNanoGongEnable()) {
        $('#nanogong').show();
    } else if (checkWamiEnable()) {
        $('#wami').show();
        var recordWami = $('#record-wami');
        recordWami.on('click', function() {
            setupRecorder();
            return false;
        });
    } else {
        $('#record-result').html("{{ 'RecordIsNotAvailable' | get_lang }}");
    }

    // Start web RTC code

    var format = 'webm'; // or wav
    var record = $('#record');
    var stop = document.getElementById('stop');
    var preview = document.getElementById('audio');
    var progressInfo = document.getElementById('progress-info');
    var previewBlock = document.getElementById('preview');

    function postBlob(blob, fileType, fileName) {
        // FormData
        var formData = new FormData();
        formData.append(fileType + '-filename', fileName);
        formData.append(fileType + '-blob', blob);

        // progress-bar
        var hr = document.createElement('hr');
        progressInfo.appendChild(hr);
        var strong = document.createElement('strong');
        strong.innerHTML = fileType + ' upload progress: ';
        progressInfo.appendChild(strong);
        var progress = document.createElement('progress');
        progressInfo.appendChild(progress);

        // POST the Blob

        $.ajax({
            url:'{{ _p.web_ajax }}lp.ajax.php?a=record_audio&lp_item_id={{ lp_item_id }}',
            data: formData,
            processData: false,
            contentType: false,
            type: 'POST',
            success:function(fileURL) {
                window.location.reload();
                progressInfo.appendChild(document.createElement('hr'));
                var mediaElement = document.createElement(fileType);
                mediaElement.src = fileURL;
                mediaElement.controls = true;
                var uniq = 'id' + (new Date()).getTime();
                mediaElement.id = uniq;
                $(previewBlock).html('');

                previewBlock.appendChild(mediaElement);
                mediaElement.play();
                $(progressInfo).html('');
                window.location.reload();
            }
        });
    }

    var recordAudio, recordVideo;

    record.on('click', function() {

        record.disabled = true;
        var myURL = (window.URL || window.webkitURL || {});
        if (navigator.getUserMedia) {
            navigator.getUserMedia({
                    audio: true,
                    video: false
            },
            function(stream) {
                if (window.IsChrome) stream = new window.MediaStream(stream.getAudioTracks());
                preview.src = myURL.createObjectURL(stream);
                    console.log(preview.src);
                preview.play();

                recordAudio = RecordRTC(stream, {
                    type: 'audio'
                });
                recordAudio.startRecording();

                /*recordVideo = RecordRTC(stream, {
                    type: 'video'
                });*/
                //recordVideo.startRecording();
                stop.disabled = false;
            },
            function(err) {
                console.log("The following error occured: " + err);
                return false;
            });
        }
    });

    stop.onclick = function() {
        record.disabled = false;
        stop.disabled = true;

        var fileName = Math.round(Math.random() * 99999999) + 99999999;

        recordAudio.stopRecording();

        postBlob(recordAudio.getBlob(), 'audio', fileName + '.' + format);

        //recordVideo.stopRecording();
        //PostBlob(recordVideo.getBlob(), 'video', fileName + '.webm');
        preview.src = '';
    };

    // End web RTC code
});

</script>
