/* For licensing terms, see /license.txt */

/**
    Learning Path minipanel - Chamilo 1.8.8
    Adding mini panel to browse Learning Paths
    Requirements: JQuery 1.4.4, JQuery UI 1.8.7
    @author Alberto Torreblanca @albert1t0
    @author Julio Montoya Cleaning/fixing code
    @author Alex Aragon Cleaning/fixing code update
**/

$(document).ready(function() {

    $('#touch-button').click(function() {

        $('#learning_path_left_zone').toggle("slow", function(){
            $('#learning_path_right_zone').toggleClass('total');
            $(function(){
                $('#learning_path_right_zone').slideToggle(300);
                $('#control-bottom').toggle("slow");

            });
        });
        $(this).toggleClass('show-touch');
        $('#learning_path_right_zone').slideToggle(300);

    });

    // effects items scorm content
    $('.scorm_item_normal, .scorm-previous, .scorm-next').on('click', function () {
        $('#learning_path_right_zone').fadeOut('slow');

        var loadLearningPathRigthZone = function () {
            $('#learning_path_right_zone').fadeIn(100);

            $('#learning_path_right_zone iframe').off('load', loadLearningPathRigthZone);
        };

        $('#learning_path_right_zone iframe').on('load', loadLearningPathRigthZone);
    });
    

});
