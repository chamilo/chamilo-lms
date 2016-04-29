<div id="record-audio-recordrtc" class="text-center">
    <p>
        <span class="fa fa-microphone fa-5x fa-fw" aria-hidden="true"></span>
        <span class="sr-only">{{ 'RecordAudio'|get_lang }}</span>
    </p>
    <button class="btn btn-primary" type="button" id="btn-start-record">
        <span class="fa fa-circle fa-fw" aria-hidden="true"></span> {{ 'StartRecordingAudio'|get_lang }}
    </button>
    <button class="btn btn-success" type="button" id="btn-stop-record" disabled>
        <span class="fa fa-square fa-fw" aria-hidden="true"></span> {{ 'StopRecordingAudio'|get_lang }}
    </button>
</div>

<div id="record-audio-wami" class="wami-container"></div>

<script>
    $(document).on('ready', function () {
        function useRecordRTC(){
            $('#record-audio-recordrtc').show();

            var mediaConstraints = {audio: true},
                    recordRTC = null,
                    btnStart = $('#btn-start-record'),
                    btnStop = $('#btn-stop-record');

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

                    btnStop.prop('disabled', false);
                    btnStart.prop('disabled', true);
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
                    var recordedBlob = recordRTC.getBlob(),
                            fileName = Math.round(Math.random() * 99999999) + 99999999,
                            fileExtension = '.' + recordedBlob.type.split('/')[1];

                    var formData = new FormData();
                    formData.append('audio-filename', fileName + fileExtension);
                    formData.append('audio-blob', recordedBlob, 'audio' + fileExtension);

                    $.ajax({
                        url: '{{ _p.web_ajax }}lp.ajax.php?a=record_audio&lp_item_id={{ lp_item_id }}',
                        data: formData,
                        processData: false,
                        contentType: false,
                        type: 'POST',
                        success: function (fileURL) {
                            if (!fileURL) {
                                return;
                            }

                            window.location.reload();
                        }
                    });

                    btnStop.prop('disabled', true);
                    btnStart.prop('disabled', false);
                });
            });
        }

        function useWami(){
            $('#record-audio-wami').show();

            function setupGUI() {
                var gui = new Wami.GUI({
                    id : 'record-audio-wami',
                    singleButton : true,
                    recordUrl : '{{ _p.web_ajax }}record_audio_wami.ajax.php?' + $.param({
                        waminame: 'rec_' + (new Date()).getTime() + '.wav',
                        wamidir: '{{ cur_dir_path }}',
                        wamiuserid: {{ _u.user_id }},
                        lp_item_id: {{ lp_item_id }}
                    }),
                    buttonUrl : '{{ _p.web_lib }}wami-recorder/buttons.png',
                    buttonNoUrl: '{{ _p.web_img }}blank.gif',
                    onRecordFinish: function() {
                        $('#start-recording').hide();
                        window.location.reload();
                    }
                });

                gui.setPlayEnabled(false);
            }

            Wami.setup({
                id : "record-audio-wami",
                onReady : setupGUI,
                swfUrl: '{{ _p.web_lib }}wami-recorder/Wami.swf'
            });
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
