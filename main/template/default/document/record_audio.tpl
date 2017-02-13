<div class="alert alert-warning">
    <span class="fa fa-warning fa-fw" aria-hidden="true"></span> {{ 'WamiNeedFilename'|get_lang }}
</div>

<div id="record-audio-recordrtc" class="row text-center">
    <form>
        <div class="row">
            <div class="col-sm-4 col-sm-offset-4">
                <div class="form-group">
                    <span class="fa fa-microphone fa-5x fa-fw" aria-hidden="true"></span>
                    <span class="sr-only">{{ 'RecordAudio'|get_lang }}</span>
                </div>
                <div class="form-group">
                    <input type="text" name="audio_title" id="audio-title-rtc" class="form-control" placeholder="{{ 'InputNameHere'|get_lang }}">
                </div>
            </div>
        </div>
        <div class="text-center">
            <div class="form-group">
                <button class="btn btn-primary" type="button" id="btn-start-record">
                    <span class="fa fa-circle fa-fw" aria-hidden="true"></span> {{ 'StartRecordingAudio'|get_lang }}
                </button>
                <button class="btn btn-danger" type="button" id="btn-stop-record" disabled>
                    <span class="fa fa-square fa-fw" aria-hidden="true"></span> {{ 'StopRecordingAudio'|get_lang }}
                </button>
                <button class="btn btn-success" type="button" id="btn-save-record" disabled>
                    <span class="fa fa-send fa-fw" aria-hidden="true"></span> {{ 'SaveRecordedAudio'|get_lang }}
                </button>
            </div>
            <div class="form-group">
                <audio class="skip hidden center-block" controls id="record-preview"></audio>
            </div>
        </div>
    </form>
</div>

<div class="row" id="record-audio-wami">
    <div class="col-sm-3 col-sm-offset-3">
        <br>
        <form>
            <div class="form-group">
                <input type="text" name="audio_title" id="audio-title-wami" class="form-control" placeholder="{{ 'InputNameHere'|get_lang }}">
            </div>
            <div class="form-group text-center">
                <button class="btn btn-primary" type="button" id="btn-activate-wami">
                    <span class="fa fa-check fa-fw" aria-hidden=""></span> {{ 'Activate'|get_lang }}
                </button>
            </div>
        </form>
    </div>
    <div class="col-sm-3">
        <div id="record-audio-wami-container" class="wami-container"></div>
    </div>
</div>

<script>
    $(document).on('ready', function() {
        function useRecordRTC() {
            $('#record-audio-recordrtc').show();

            var audioTitle = '';

            var mediaConstraints = {audio: true},
                recordRTC = null,
                btnStart = $('#btn-start-record'),
                btnStop = $('#btn-stop-record'),
                btnSave = $('#btn-save-record'),
                tagAudio = $('#record-preview');

            btnStart.on('click', function () {
                audioTitle = $('#audio-title-rtc').val();

                if (!$.trim(audioTitle)) {
                    return;
                }

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

                    $('#audio-title-rtc').prop('readonly', true);
                    btnSave.prop('disabled', true);
                    btnStop.prop('disabled', false);
                    btnStart.prop('disabled', true);
                    tagAudio.removeClass('show').addClass('hidden');
                }

                function errorCallback(error) {
                    alert(error.message);
                }
            });

            btnStop.on('click', function () {
                if (!recordRTC) {
                    return;
                }

                recordRTC.stopRecording(function (audioURL) {
                    btnStart.prop('disabled', false);
                    btnStop.prop('disabled', true);
                    btnSave.prop('disabled', false);
                    tagAudio
                            .removeClass('hidden')
                            .addClass('show')
                            .prop('src', audioURL);
                });
            });

            btnSave.on('click', function () {
                if (!recordRTC) {
                    return;
                }

                var recordedBlob = recordRTC.getBlob();

                if (!recordedBlob) {
                    return;
                }

                var fileName = audioTitle,
                        fileExtension = '.' + recordedBlob.type.split('/')[1];

                var formData = new FormData();
                formData.append('audio_blob', recordedBlob, audioTitle + fileExtension);
                formData.append('audio_dir', '{{ directory }}');

                $.ajax({
                    url: '{{ _p.web_ajax }}record_audio_rtc.ajax.php',
                    data: formData,
                    processData: false,
                    contentType: false,
                    type: 'POST',
                    success: function (fileURL) {
                        if (!fileURL) {
                            return;
                        }

                        $('#audio-title-rtc').prop('readonly', false);
                        btnSave.prop('disabled', true);
                        btnStop.prop('disabled', true);
                        btnStart.prop('disabled', false);

                        window.location.reload();
                    }
                });
            });
        }

        function useWami() {
            $('#record-audio-wami').show();

            var audioTitle = '';

            $('#btn-activate-wami').on('click', function (e) {
                e.preventDefault();

                audioTitle = $('#audio-title-wami').val();

                if (!$.trim(audioTitle)) {
                    return;
                }

                $('#audio-title-wami').prop('readonly', true);
                $(this).prop('disabled', true);

                Wami.setup({
                    id : "record-audio-wami-container",
                    onReady : setupGUI,
                    swfUrl: '{{ _p.web_lib }}wami-recorder/Wami.swf'
                });
            });

            function setupGUI() {
                var gui = new Wami.GUI({
                    id : 'record-audio-wami-container',
                    singleButton : true,
                    recordUrl : '{{ _p.web_ajax }}record_audio_wami.ajax.php?' + $.param({
                        waminame: audioTitle + '.wav',
                        wamidir: '{{ directory }}',
                        wamiuserid: {{ user_id }}
                    }),
                    buttonUrl : '{{ _p.web_lib }}wami-recorder/buttons.png',
                    buttonNoUrl: '{{ _p.web_img }}blank.gif',
                    onRecordFinish: function() {
                        window.location.reload();
                    }
                });

                gui.setPlayEnabled(false);
            }
        }

        $('#record-audio-recordrtc, #record-audio-wami').hide();

        var webRTCIsEnabled = navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.getUserMedia ||
                navigator.mediaDevices.getUserMedia;

        if (webRTCIsEnabled) {
            useRecordRTC();

            return;
        }

        useWami();
    });
</script>
