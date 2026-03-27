(function () {
    var dialog = document.getElementById('em-modal-start');
    var $btnStartExercise = $('.exercise_overview_options a');
    var $bodyTerms = $('#em-terms-body');
    var $bodyCamera = $('#em-camera-body');
    var $bodyIdDoc = $('#em-iddoc-body');
    var $bodyStudent = $('#em-student-body');

    var $footTerms = $('#em-terms-footer');
    var $footCamera = $('#em-camera-footer');

    var $btnSnap = $('#btn-snap');
    var $btnRetry = $('#btn-retry');
    var $btnNext = $('#btn-next');

    var $imgIdDoc = $('#img-iddoc');
    var $imgLearner = $('#img-learner');

    var $txtIdDocInstructions = $('#txt-iddoc-img-instructions');
    var $txtLearnerInstructions = $('#txt-learner-img-instructions');
    var $imgIdDocPlaceholder = $('#img-iddoc-placeholder');
    var $imgLearnerPlaceholder = $('#img-learner-placeholder');

    var hasIdDoc = false;
    var hasLearner = false;

    var imgIdDoc = null;
    var imgLearner = null;

    // Prevent closing on backdrop click and ESC key (static modal behavior).
    dialog.addEventListener('cancel', function (e) {
        e.preventDefault();
    });
    dialog.addEventListener('click', function (e) {
        if (e.target === dialog) {
            e.preventDefault();
        }
    });

    if ($btnStartExercise.length > 0) {
        dialog.showModal();
    }

    $btnStartExercise.addClass('disabled').attr({'aria-disabled': 'true', 'disabled': 'true'});

    $("#btn-accept").on('click', function (e) {
        e.preventDefault();

        $bodyTerms.hide();
        $footTerms.hide();

        $bodyCamera.show();
        $footCamera.show();

        Webcam.set({
            height: 480,
            width: 640,
        });
        Webcam.attach('#monitoring-camera');
        Webcam.on('live', function () {
            $txtIdDocInstructions.show();
            $imgIdDocPlaceholder.show();
            $txtLearnerInstructions.hide();
            $imgLearnerPlaceholder.hide();

            $btnSnap.prop({disabled: false}).focus();
            $('#monitoring-camera video').addClass('embed-responsive-item');
        });
    });

    $btnSnap.on('click', function (e) {
        e.preventDefault();

        $btnSnap.prop({disabled: true});
        $btnRetry.prop({disabled: true});
        $btnNext.prop({disabled: true});

        snap()
            .done(function () {
                $btnRetry.prop({disabled: false});
                $btnNext.prop({disabled: false});
            });
    });

    $btnRetry.on('click', function (e) {
        e.preventDefault();

        $btnSnap.prop({disabled: false}).focus();
        $btnRetry.prop({disabled: true});
        $btnNext.prop({disabled: true});

        if (hasIdDoc && !hasLearner) {
            $bodyCamera.show();
            $bodyIdDoc.hide();

            hasIdDoc = false;
            hasLearner = false;
        } else if (hasIdDoc && hasLearner) {
            $bodyCamera.show();
            $bodyStudent.hide();

            hasIdDoc = true;
            hasLearner = false;
        }
    });

    $btnNext.on('click', function (e) {
        e.preventDefault();

        $btnRetry.prop({disabled: true});

        if (hasIdDoc && !hasLearner) {
            $bodyIdDoc.hide();
            $bodyCamera.show();

            $txtIdDocInstructions.hide();
            $imgIdDocPlaceholder.hide();
            $txtLearnerInstructions.show();
            $imgLearnerPlaceholder.show();

            $btnSnap.prop({disabled: false}).focus();
        } else if (hasIdDoc && hasLearner) {
            $btnNext.prop({disabled: true});
            $btnSnap.prop({disabled: true});

            Webcam.reset();

            sendData().done(function () {
                $btnStartExercise.removeClass('disabled').removeAttr('aria-disabled');

                window.location = $btnStartExercise.prop('href');

                dialog.close();
            });
        }
    });

    $(window).on('keyup', function (e) {
        if (32 === event.which && !$btnSnap.prop('disabled')) {
            e.preventDefault();

            $btnSnap.trigger('click');
        }
    });

    function snap() {
        var deferred = $.Deferred();

        Webcam.snap(function (dataUri) {
            var $imgSnapshot = $('<img>')
                .prop({src: dataUri, id: 'img-snapshot'})
                .addClass('img-responsive');

            if (!hasIdDoc && !hasLearner) {
                $imgIdDoc.html($imgSnapshot);

                $bodyCamera.hide();
                $bodyIdDoc.show();

                hasIdDoc = true;
                hasLearner = false;

                imgIdDoc = dataUri;
            } else if (hasIdDoc && !hasLearner) {
                $imgLearner.html($imgSnapshot);

                $bodyCamera.hide();
                $bodyStudent.show();

                hasIdDoc = true;
                hasLearner = true;

                imgLearner = dataUri;
            }

            deferred.resolve();
        });

        return deferred.promise();
    }

    function sendData() {
        var exerciseId = dialog.dataset.exerciseId;

        var rawImgIdDoc = imgIdDoc.replace(/^data:image\/\w+;base64,/, '');
        var blobImgIdDoc = new Blob( [ Webcam.base64DecToArr(rawImgIdDoc) ], {type: 'image/jpeg'} );

        var rawImgLearner = imgLearner.replace(/^data:image\/\w+;base64,/, '');
        var blobImgLearner = new Blob( [ Webcam.base64DecToArr(rawImgLearner) ], {type: 'image/jpeg'} );

        var formData = new FormData();
        formData.append('iddoc', blobImgIdDoc, 'iddoc.jpg');
        formData.append('learner', blobImgLearner, 'learner.jpg');
        formData.append('exercise_id', exerciseId);
        formData.append('cid', window.chamiloCidReq.course.id)
        formData.append('sid', window.chamiloCidReq.session?.id)

        return $.ajax({
            url: '/plugin/ExerciseMonitoring/pages/start.ajax.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
        });
    }
})();