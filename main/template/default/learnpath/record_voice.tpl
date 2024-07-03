<div id="record-audio-recordrtc" class="text-center">
    <p>
        <span class="fa fa-microphone fa-5x fa-fw" aria-hidden="true"></span>
        <span class="sr-only">{{ 'RecordAudio'|get_lang }}</span>
        <div id="timer" style="display: none">
            <h2>
                <div class="label label-danger">
                    <span id="hour">00</span>
                    <span class="divider">:</span>
                    <span id="minute">00</span>
                    <span class="divider">:</span>
                    <span id="second">00</span>
                </div>
            </h2>
            <br />
        </div>
        <div class="form-group">
            <input type="text" name="audio_title" id="audio-title-rtc" class="form-control" placeholder="{{ 'InputNameHere'|get_lang }}" />
        </div>
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
    $(function() {
        function startTimer() {
            $("#timer").show();
            var timerData = {
                hour: parseInt($("#hour").text()),
                minute: parseInt($("#minute").text()),
                second: parseInt($("#second").text())
            };

            clearInterval(window.timerInterval);
            window.timerInterval = setInterval(function(){
                // Seconds
                timerData.second++;
                if (timerData.second >= 60) {
                    timerData.second = 0;
                    timerData.minute++;
                }

                // Minutes
                if (timerData.minute >= 60) {
                    timerData.minute = 0;
                    timerData.hour++;
                }

                $("#hour").text(timerData.hour < 10 ? '0' + timerData.hour : timerData.hour);
                $("#minute").text(timerData.minute < 10 ? '0' + timerData.minute : timerData.minute);
                $("#second").text(timerData.second < 10 ? '0' + timerData.second : timerData.second);
            }, 1000);
        }

        function stopTimer() {
            $("#hour").text('00');
            $("#minute").text('00');
            $("#second").text('00');
            $("#timer").hide();
        }

        function pauseTimer() {
            clearInterval(window.timerInterval);
        }

        function useRecordRTC() {
            $('#record-audio-recordrtc').show();

            var audioTitle = $('#audio-title-rtc');
            var mediaConstraints = {audio: true},
                    recordRTC = null,
                    btnStart = $('#btn-start-record'),
                    btnStop = $('#btn-stop-record');

            btnStart.on('click', function () {
                if ('' === audioTitle.val()) {
                    alert('{{ 'TitleIsRequired'|get_lang | escape }} ');

                    return false;
                }

                function successCallback(stream) {
                    stopTimer();
                    startTimer();
                    recordRTC = RecordRTC(stream, {
                        recorderType: RecordRTC.StereoAudioRecorder,
                        type: 'audio',
                        mimeType: 'audio/wav',
                        numberOfAudioChannels: 2
                    });
                    recordRTC.startRecording();

                    btnStop.prop('disabled', false);
                    btnStart.prop('disabled', true);
                }

                function errorCallback(error) {
                    stopTimer();
                    alert(error);
                }

                if(!!(navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia)) {
                    navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia;
                    navigator.getUserMedia(mediaConstraints, successCallback, errorCallback);
                    return;
                }

                navigator.mediaDevices.getUserMedia(mediaConstraints)
                    .then(successCallback)
                    .catch(errorCallback);
            });

            btnStop.on('click', function () {
                if (!recordRTC) {
                    return;
                }

                stopTimer();
                recordRTC.stopRecording(function (audioURL) {
                    var recordedBlob = recordRTC.getBlob(),
                            fileName = Math.round(Math.random() * 99999999) + 99999999,
                            fileExtension = '.' + recordedBlob.type.split('/')[1];

                    var formData = new FormData();
                    formData.append('audio-filename', fileName + fileExtension);
                    formData.append('audio-blob', recordedBlob, 'audio' + fileExtension);
                    formData.append('audio-title', audioTitle.val());

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
          (navigator.mediaDevices && navigator.mediaDevices.getUserMedia);

        if (webRTCIsEnabled) {
            useRecordRTC();

            return;
        }

        useWami();
    });
</script>
