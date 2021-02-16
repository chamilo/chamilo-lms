/* For licensing terms, see /license.txt */

window.RecordAudio = (function () {
    var timerInterval = 0,
        $txtTimer = null;

    function startTimer() {
        stopTimer();

        $txtTimer = $('#txt-timer');

        $txtTimer.text('00:00').css('visibility', 'visible');

        var timerData = {
            hour: 0,
            minute: 0,
            second: 0
        };

        timerInterval = setInterval(function(){
            timerData.second++;

            if (timerData.second >= 60) {
                timerData.second = 0;
                timerData.minute++;
            }


            $txtTimer.text(
                function () {
                    var txtSeconds = timerData.minute < 10 ? '0' + timerData.minute : timerData.minute,
                        txtMinutes = timerData.second < 10 ? '0' + timerData.second : timerData.second;

                    return txtSeconds + ':' + txtMinutes;
                }
            );
        }, 1000);
    }

    function stopTimer() {
        if (timerInterval) {
            clearInterval(timerInterval);
        }

        if ($txtTimer) {
            $txtTimer.css('visibility', 'hidden');
        }
    }

    function useRecordRTC(rtcInfo) {
        $(rtcInfo.blockId).show();

        var mediaConstraints = {audio: true},
            localStream = null,
            recordRTC = null,
            btnStart = $(rtcInfo.btnStartId),
            btnStop = $(rtcInfo.btnStopId),
            tagAudio = $(rtcInfo.plyrPreviewId);

        function saveAudio() {
            var recordedBlob = recordRTC.getBlob();

            if (!recordedBlob) {
                return;
            }

            var btnStopText = btnStop.html();
            var fileExtension = recordedBlob.type.split('/')[1];

            var formData = new FormData();
            formData.append('audio', recordedBlob, 'audio.' + fileExtension);

            for (var prop in rtcInfo.data) {
                if (!rtcInfo.data.hasOwnProperty(prop)) {
                    continue;
                }

                formData.append(prop, rtcInfo.data[prop]);
            }

            $.ajax({
                url: _p.web_plugin + 'whispeakauth/ajax/record_audio.php',
                data: formData,
                processData: false,
                contentType: false,
                type: 'POST',
                beforeSend: function () {
                    btnStart.prop('disabled', true);
                    btnStop.prop('disabled', true).text(btnStop.data('loadingtext'));
                }
            }).done(function (response) {
                if (response.text) {
                    $('#txt-sample-text').text(response.text);
                }

                $('#messages-deck').html(response.resultHtml);

                if ($('#messages-deck > .alert.alert-success').length > 0) {
                    tagAudio.parents('#audio-wrapper').addClass('hidden').removeClass('show');
                } else {
                    tagAudio.parents('#audio-wrapper').removeClass('hidden').addClass('show');
                }

                btnStop.prop('disabled', true).html(btnStopText).parent().addClass('hidden');

                if ($('#messages-deck > .alert.alert-success').length > 0 ||
                    $('#messages-deck > .alert.alert-warning [data-reach-attempts]').length > 0
                ) {
                    btnStart.prop('disabled', true);
                } else {
                    btnStart.prop('disabled', false);
                }

                btnStart.parent().removeClass('hidden');
            });
        }

        btnStart.on('click', function () {
            tagAudio.prop('src', '');

            function successCallback(stream) {
                localStream = stream;

                recordRTC = RecordRTC(stream, {
                    recorderType: RecordRTC.StereoAudioRecorder,
                    type: 'audio',
                    mimeType: 'audio/wav',
                    numberOfAudioChannels: 2
                });
                recordRTC.startRecording();

                btnStop.prop('disabled', false).parent().removeClass('hidden');
                btnStart.prop('disabled', true).parent().addClass('hidden');
                tagAudio.removeClass('show').parents('#audio-wrapper').addClass('hidden');

                $('.fa-microphone').addClass('text-danger');

                startTimer();
            }

            function errorCallback(error) {
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

            $('.fa-microphone').removeClass('text-danger');

            stopTimer();

            recordRTC.stopRecording(function (audioURL) {
                tagAudio.prop('src', audioURL);

                localStream.getTracks()[0].stop();

                saveAudio();
            });
        });
    }

    return {
        init: function (rtcInfo) {
            $(rtcInfo.blockId).hide();

            var userMediaEnabled = (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) ||
                !!navigator.webkitGetUserMedia ||
                !!navigator.mozGetUserMedia ||
                !!navigator.getUserMedia;

            if (!userMediaEnabled) {
                return;
            }

            useRecordRTC(rtcInfo);
        }
    };
})();