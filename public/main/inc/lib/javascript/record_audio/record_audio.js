/* For licensing terms, see /license.txt */
window.RecordAudio = (function () {

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
    function useRecordRTC(rtcInfo) {
        $(rtcInfo.blockId).show();

        var mediaConstraints = {audio: true},
            recordRTC = null,
            btnStart = $(rtcInfo.btnStartId),
            btnPause = $(rtcInfo.btnPauseId),
            btnPlay = $(rtcInfo.btnPlayId),
            btnStop = $(rtcInfo.btnStopId),
            btnSave = rtcInfo.btnSaveId ? $(rtcInfo.btnSaveId) : null,
            tagAudio = $(rtcInfo.plyrPreviewId);

        function saveAudio() {
            var recordedBlob = recordRTC.getBlob();

            if (!recordedBlob) {
                return;
            }

            var btnSaveText = btnSave ? btnSave.html() : '';

            var fileName = 'oral_expression_' + rtcInfo.tExerciseId + '_' + rtcInfo.questionId;

            var formData = new FormData();
            formData.append('type', rtcInfo.type);
            formData.append('audio_blob', recordedBlob, fileName);
            formData.append('t_exercise', rtcInfo.tExerciseId);
            formData.append('question', rtcInfo.questionId);

            var courseParams = rtcInfo.cidReq.replaceAll('&amp;', '&');

            $.ajax({
                url: rtcInfo.recordAudioUrl + '?a=' + courseParams,
                data: formData,
                processData: false,
                contentType: false,
                type: 'POST',
                beforeSend: function () {
                    btnStart.prop('disabled', true);
                    btnPause.prop('disabled', true);
                    btnPlay.prop('disabled', true);
                    btnStop.prop('disabled', true);
                    if (btnSave) {
                        btnSave.prop('disabled', true).text(btnSave.data('loadingtext'));
                    }
                }
            }).done(function (response) {
                $(response.message).insertAfter($(rtcInfo.blockId).find('.well'));
            }).always(function () {
                if (btnSave) {
                    btnSave.prop('disabled', true).addClass('hidden').html(btnSaveText);
                }
                btnStop.prop('disabled', true).addClass('hidden');
                btnPause.prop('disabled', true).addClass('hidden');
                btnStart.prop('disabled', false).removeClass('hidden');
            });
        }

        btnStart.on('click', function () {
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

                if (btnSave) {
                    btnSave.prop('disabled', true).addClass('hidden');
                }
                btnStop.prop('disabled', false).removeClass('hidden');
                btnStart.prop('disabled', true).addClass('hidden');
                btnPause.prop('disabled', false).removeClass('hidden');
                tagAudio.removeClass('show').addClass('hidden');
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

        btnPause.on('click', function () {
            if (!recordRTC) {
                return;
            }
            pauseTimer();

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
            startTimer();
        });

        btnStop.on('click', function () {
            if (!recordRTC) {
                return;
            }

            stopTimer();
            recordRTC.stopRecording(function (audioURL) {
                btnStart.prop('disabled', false).removeClass('hidden');
                btnPause.prop('disabled', true).addClass('hidden');
                btnStop.prop('disabled', true).addClass('hidden');

                if (btnSave) {
                    btnSave.prop('disabled', false).removeClass('hidden');
                } else {
                    saveAudio();
                }

                tagAudio
                    .removeClass('hidden')
                    .addClass('show')
                    .prop('src', audioURL);
            });
        });

        if (btnSave) {
            btnSave.on('click', function () {
                if (!recordRTC) {
                    return;
                }

                saveAudio();
            });
        }
    }

    return {
        init: function (rtcInfo) {
            $(rtcInfo.blockId).hide();

            var webRTCIsEnabled = navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.getUserMedia ||
                navigator.mediaDevices.getUserMedia;

            if (webRTCIsEnabled) {
                useRecordRTC(rtcInfo);

                return;
            }
        }
    }
})();
