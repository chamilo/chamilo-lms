(function () {
    var dialog = document.getElementById('em-modal-start');
    var $btnStartExercise = $('.exercise_overview_options a');

    if ($btnStartExercise.length > 0) {
        dialog.showModal();
    }

    document.getElementById('btn-accept-simple').addEventListener('click', function () {
        dialog.close();
    });

    dialog.addEventListener('close', function () {
        $btnStartExercise.removeClass('disabled').removeAttr('aria-disabled');

        window.location = $btnStartExercise.prop('href');
    });
})();