/* For licensing terms, see /license.txt */
/*
 Learning Path minipanel - Chamilo 1.8.8
 Adding mini panel to browse Learning Paths
 Requirements: JQuery 1.4.4, JQuery UI 1.8.7
 @author Alberto Torreblanca @albert1t0
 @author Julio Montoya Cleaning/fixing some code
 **/

function minipanel(){
    // Construct mini panel
    var panel = $('#lp_navigation_elem div:first').clone();

    $(panel).attr('id', 'control');
    $('#learning_path_main').append(panel);

    $('#learning_path_main #control tr').after('<tr></tr>');
    $('#learning_path_main #control tr:eq(1)').append($('#progress_bar').html());
    $('#learning_path_main #control tr:eq(1) #progress_img_limit_left').attr('height','5');
    $('#learning_path_main #control tr:eq(1) #progress_img_full').attr('height','5');
    $('#learning_path_main #control tr:eq(1) #progress_img_limit_middle').attr('height','5');
    $('#learning_path_main #control tr:eq(1) #progress_img_empty').attr('height','5');
    $('#learning_path_main #control tr:eq(1) #progress_bar_img_limit_right').attr('height','5');
    $('#learning_path_main #control tr:eq(1) #progress_text').remove();
    $('#learning_path_main #control tr:eq(1) div').css('width','');

    $('#learning_path_main #control .buttons').attr('text-align','center');
    //$('#content_id').css({ height: $('#content_id').height() - ($('#control').height() + 10) });

    $('#learning_path_main #control .buttons img').click(function(){
        $('#learning_path_main #control tr:eq(1)').remove();
        minipanel();
    });
}

$(document).ready(function(){

   $('#touch-button').click(function(){

        $('#learning_path_left_zone').toggle("slow",function(){
                $('#learning_path_right_zone').toggleClass('total');
                $(function(){
                    $('#learning_path_right_zone').slideToggle(300);
                    minipanel();
                });
        }
        );
       $(this).toggleClass('show-touch');
       $('#learning_path_right_zone').slideToggle(300);
   });
    //effects items scorm content
    $('.scorm_item_normal').click(function(){
        $('#learning_path_right_zone').fadeOut(300);
        setTimeout(function(){
            $('#learning_path_right_zone').fadeIn(300);
        },300);

    });
    $('.scorm-previous').click(function(){
        $('#learning_path_right_zone').fadeOut(300);
        setTimeout(function(){
            $('#learning_path_right_zone').fadeIn(300);
        },300);

    });
    $('.scorm-next').click(function(){
        $('#learning_path_right_zone').fadeOut(300);
        setTimeout(function(){
            $('#learning_path_right_zone').fadeIn(300);
        },300);

    });
});
