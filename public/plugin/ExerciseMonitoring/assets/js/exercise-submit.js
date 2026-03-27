(function () {
    var camera = document.getElementById('monitoring-camera');
    var exerciseId = camera.dataset.exerciseId;
    var exerciseType = parseInt(camera.dataset.exerciseType, 10);

    var ALL_ON_ONE_PAGE = exerciseType === 1;
    var ONE_PER_PAGE = exerciseType === 2;

    var $btnSaveNow = $('button[name="save_now"]');
    var $btnEndTest = $('button[name="validate_all"]');

    Webcam.set({
        height: 480,
        width: 640,
    });
    Webcam.attach('#monitoring-camera');
    Webcam.on('live', function () {
        if (ALL_ON_ONE_PAGE) {
            snapAndSendData(0);
        } else if (ONE_PER_PAGE) {
            snapByQuestion();
        }
    });

    if (ALL_ON_ONE_PAGE) {
        $btnEndTest.on('click', function () {
            snapAndSendData(0);
        });
    } else if (ONE_PER_PAGE) {
        $btnSaveNow.on('click', function () {
            snapByQuestion();
        });
    }

    function snapAndSendData(levelId) {
        Webcam.snap(function (dataUri) {
            sendData(levelId, dataUri);
        });
    }

    function snapByQuestion() {
        var questionId = $btnSaveNow.data('question') || 0;

        snapAndSendData(questionId);
    }

    function sendData(questionId, imageUri) {
        var rawImg = imageUri.replace(/^data:image\/\w+;base64,/, '');
        var blobImg = new Blob( [ Webcam.base64DecToArr(rawImg) ], {type: 'image/jpeg'} );

        var formData = new FormData();
        formData.append('exercise_id', exerciseId);
        formData.append('level_id', questionId);
        formData.append('snapshot', blobImg, 'snapshot.jpg');
        formData.append('cid', window.chamiloCidReq.course.id);
        formData.append('sid', window.chamiloCidReq.session?.id);

        return $.ajax({
            url: '/plugin/ExerciseMonitoring/pages/exercise_submit.ajax.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
        });
    }
})();