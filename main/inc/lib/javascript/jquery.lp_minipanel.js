/*******************************************
 Learning Path minipanel - Chamilo 1.8.8
 Adding mini panel to browse Learning Paths
 Requirements: JQuery 1.4.4, JQuery UI 1.8.7
 Alberto Torreblanca @albert1t0
 *******************************************/
 function display_hide_toc(){

      // Copy little progress bar in <tr></tr>
      function minipb(){
        $('#learning_path_main #control tr').after('<tr></tr>');
        $('#learning_path_main #control tr:eq(1)').append($('#progress_bar').html());
        $('#learning_path_main #control tr:eq(1) #progress_img_limit_left').attr('height','5');
        $('#learning_path_main #control tr:eq(1) #progress_img_full').attr('height','5');
        $('#learning_path_main #control tr:eq(1) #progress_img_limit_middle').attr('height','5');
        $('#learning_path_main #control tr:eq(1) #progress_img_empty').attr('height','5');
        $('#learning_path_main #control tr:eq(1) #progress_bar_img_limit_right').attr('height','5');
        $('#learning_path_main #control tr:eq(1) #progress_text').remove();
        $('#learning_path_main #control tr:eq(1) div').css('width','');
      }

      if($('#hide_bar').position().left == 280){
      // Construct mini panel
      var panel = $('#lp_navigation_elem div:first').clone();
      $(panel).attr('id','control');
      $('#learning_path_main').append(panel);
      minipb();
      $('#learning_path_main #control .buttons').attr('align','center');
      $('#learning_path_main  #control').css(
        { margin: "auto",
          width: "132px",
          height: "34px",
          position: "absolute",
          top: "5px",
          left:"15px",
          backgroundColor: "white",
          backgroundImage: "url(../img/minipanelback.png)",
          paddingTop: "8px",
          paddingBottom: "8px",
          borderRadius: "4px 4px 4px 4px",
          opacity: "0.8",
          cursor: "move"
        });
      $('#learning_path_main  #control table').attr('align','center');
      $('#learning_path_main  #control').draggable(
      { iframeFix: true,
        stack: "#learning_path_right_zone",
        cursor: "move"
      });
      $('#learning_path_main #control .buttons img').click(function()
      { $('#learning_path_main #control tr:eq(1)').remove();
        minipb();
      });
      // Hiding navigation left zone
      $('#learning_path_left_zone').hide(50);
      $('#learning_path_right_zone').css('margin-left','10px');
      $('#hide_bar table').css('backgroundImage','url(../img/hide2.png)').css('backgroundColor','#EEEEEE');

    }else{
      // Show navigation left zone
      $('#hide_bar table').css('backgroundImage','url(../img/hide0.png)').css('backgroundColor','#EEEEEE');
      $('#learning_path_right_zone').css('marginLeft','290px');
      $('#learning_path_left_zone').show(50);
      $('#learning_path_main  #control').remove();
    }
 }

(function($){
  $(document).ready(function() {

   //Adding div to hide panel
    $('#learning_path_right_zone').
         before('<div id="hide_bar" style="float: left; width: 10px; height: 100%;">' +
        '<table style="border: 0 none; width: 100%; height: 100%; cursor: pointer; background-color: #EEEEEE">' +
        '<tr> <td> </td></tr></table></div>');
    if($('#hide_bar').position().left == 0)
    	$('#hide_bar table').css({backgroundImage: "url(../img/hide2.png)", backgroundRepeat: "no-repeat", backgroundPosition: "center center"})
		else
    	$('#hide_bar table').css({backgroundImage: "url(../img/hide0.png)", backgroundRepeat: "no-repeat", backgroundPosition: "center center"})

    //Adding effects to hide bar
    $('#hide_bar table').hover(function (){
            if($('#hide_bar').position().left == 280)
              $(this).css('backgroundImage','url(../img/hide1.png)').css('backgroundColor','#888888');
            else if($('#hide_bar').position().left == 0)
              $(this).css('backgroundImage','url(../img/hide3.png)').css('backgroundColor','#888888');
        },function (){
            if($('#hide_bar').position().left == 280)
              $(this).css('backgroundImage','url(../img/hide0.png)').css('backgroundColor','#EEEEEE');
            else if($('#hide_bar').position().left == 0)
              $(this).css('backgroundImage','url(../img/hide2.png)').css('backgroundColor','#EEEEEE');
        }
      );

    // Adding funcionality
    $('#hide_bar table').toggle(
		display_hide_toc, display_hide_toc);
  });
})(jQuery);
