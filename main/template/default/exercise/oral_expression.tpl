<div id="record-audio-recordrtc-{{ question_id }}" class="row text-center">
    <div class="col-sm-4 col-sm-offset-4">
        <div class="form-group">
            <span class="fa fa-microphone fa-5x fa-fw" aria-hidden="true"></span>
            <span class="sr-only">{{ 'RecordAudio'|get_lang }}</span>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <button class="btn btn-primary" type="button" id="btn-start-record-{{ question_id }}">
                <span class="fa fa-circle fa-fw" aria-hidden="true"></span> {{ 'StartRecordingAudio'|get_lang }}
            </button>
            <button class="btn btn-primary hidden" type="button" id="btn-pause-record-{{ question_id }}" disabled>
                <span class="fa fa-pause fa-fw" aria-hidden="true"></span> {{ 'PauseRecordingAudio'|get_lang }}
            </button>
            <button class="btn btn-primary hidden" type="button" id="btn-play-record-{{ question_id }}" disabled>
                <span class="fa fa-play fa-fw" aria-hidden="true"></span> {{ 'PlayRecordingAudio'|get_lang }}
            </button>
            <button class="btn btn-danger hidden" type="button" id="btn-stop-record-{{ question_id }}" disabled>
                <span class="fa fa-square fa-fw" aria-hidden="true"></span> {{ 'StopRecordingAudio'|get_lang }}
            </button>
            <button class="btn btn-success hidden" type="button" id="btn-save-record-{{ question_id }}" disabled>
                <span class="fa fa-send fa-fw" aria-hidden="true"></span> {{ 'SaveRecordedAudio'|get_lang }}
            </button>
        </div>
        <div class="form-group">
            <audio class="skip hidden center-block" controls id="record-preview-{{ question_id }}"></audio>
        </div>
    </div>
</div>

<div class="row" id="record-audio-wami-{{ question_id }}">
    <div class="col-sm-4 col-sm-offset-4 text-center">
        <div id="record-audio-wami-container-{{ question_id }}" class="wami-container"></div>
    </div>
</div>

<script>
$(document).on('ready', function () {
    function useRecordRTC() {
        $('#record-audio-recordrtc-{{ question_id }}').show();
        var mediaConstraints = {audio: true},
                recordRTC = null,
                btnStart = $('#btn-start-record-{{ question_id }}'),
                btnPause = $('#btn-pause-record-{{ question_id }}'),
                btnPlay = $('#btn-play-record-{{ question_id }}'),
                btnStop = $('#btn-stop-record-{{ question_id }}'),
                btnSave = $('#btn-save-record-{{ question_id }}'),
                tagAudio = $('#record-preview-{{ question_id }}');

        btnStart.on('click', function () {
            navigator.getUserMedia = navigator.getUserMedia ||
                    navigator.mozGetUserMedia ||
                    navigator.webkitGetUserMedia;

            if (navigator.getUserMedia) {
                navigator.getUserMedia(mediaConstraints, successCallback, errorCallback);
            } else if (navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia(mediaConstraints)
                        .then(successCallback).error(errorCallback);
            }

            function successCallback(stream) {
                recordRTC = RecordRTC(stream, {
                    numberOfAudioChannels: 1,
                    type: 'audio'
                });
                recordRTC.startRecording();

                btnSave.prop('disabled', true).addClass('hidden');
                btnStop.prop('disabled', false).removeClass('hidden');
                btnStart.prop('disabled', true).addClass('hidden');
                btnPause.prop('disabled', false).removeClass('hidden');
                tagAudio.removeClass('show').addClass('hidden');
            }

            function errorCallback(error) {
                alert(error.message);
            }
        });

        btnPause.on('click', function () {
            if (!recordRTC) {
                return;
            }

            btnPause.prop('disabled', true).addClass('hidden');
            btnPlay.prop('disabled', false).removeClass('hidden');
            btnStop.prop('disabled', true).addClass('hidden');
            recordRTC.pauseRecording();
        });

        btnPlay.on('click', function () {
            if (!recordRTC) {
                return;
            }

            btnPlay.prop('disabled', true).addClass('hidden');
            btnPause.prop('disabled', false).removeClass('hidden');
            btnStop.prop('disabled', false).removeClass('hidden');
            recordRTC.resumeRecording();
        });

        btnStop.on('click', function () {
            if (!recordRTC) {
                return;
            }

            recordRTC.stopRecording(function (audioURL) {
                btnStart.prop('disabled', false).removeClass('hidden');
                btnPause.prop('disabled', true).addClass('hidden');
                btnStop.prop('disabled', true).addClass('hidden');
                btnSave.prop('disabled', false).removeClass('hidden');

                tagAudio
                        .removeClass('hidden')
                        .addClass('show')
                        .prop('src', audioURL);
            });
        });

        // Download button
        btnSave.on('click', function () {
            if (!recordRTC) {
                return;
            }

            var recordedBlob = recordRTC.getBlob();
            if (!recordedBlob) {
                return;
            }

            var fileName = '{{ file_name }}',
                    fileExtension = '.' + recordedBlob.type.split('/')[1];

            var formData = new FormData();
            formData.append('audio_blob', recordedBlob, fileName + fileExtension);
            formData.append('audio_dir', '{{ directory }}');

            $.ajax({
                url: '{{ _p.web_ajax }}record_audio_rtc.ajax.php',
                data: formData,
                processData: false,
                contentType: false,
                type: 'POST'
            }).then(function () {
                btnSave.prop('disabled', true).addClass('hidden');
                btnStop.prop('disabled', true).addClass('hidden');
                btnStart.prop('disabled', false).removeClass('hidden');
            });
        });
    }

    function useWami() {
        $('#record-audio-wami-{{ question_id }}').show();

        Wami.setup({
            id: "record-audio-wami-container-{{ question_id }}",
            onReady: setupGUI,
            swfUrl: '{{ _p.web_lib }}wami-recorder/Wami.swf'
        });

        function setupGUI() {
            var gui = new Wami.GUI({
                    id: 'record-audio-wami-container-{{ question_id }}',
                    singleButton: true,
                    recordUrl: '{{ _p.web_ajax }}record_audio_wami.ajax.php?' + $.param({
                        waminame: '{{ file_name }}.wav',
                        wamidir: '{{ directory }}',
                        wamiuserid: {{ user_id }}
                    }),
                    buttonUrl: '{{ _p.web_lib }}wami-recorder/buttons.png',
                    buttonNoUrl: '{{ _p.web_img }}blank.gif'
                }
            );

            gui.setPlayEnabled(false);
        }
    }

    $('#record-audio-recordrtc-{{ question_id }}, #record-audio-wami-{{ question_id }}').hide();

    var webRTCIsEnabled = navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.getUserMedia ||
            navigator.mediaDevices.getUserMedia;

    if (webRTCIsEnabled) {
        useRecordRTC();

        return;
    }

    useWami();
});
</script>
