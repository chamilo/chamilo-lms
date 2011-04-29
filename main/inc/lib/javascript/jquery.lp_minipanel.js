
/*******************************************
 Learning Path minipanel - Chamilo 1.8.8
 Adding mini panel to browse Learning Paths
 Requirements: JQuery 1.4.4, JQuery UI 1.8.7
 Alberto Torreblanca @albert1t0
 *******************************************/

(function($){
  $(document).ready(function() {

   //Adding div to hide panel

    $('#learning_path_right_zone').
         before('<div id="hide_bar" style="float: left; width: 10px; height: 100%;">' +
        '<table style="border: 0px none; width: 100%; height: 100%; cursor: pointer; background-color: #EEEEEE">' +
        '<tr> <td> </td></tr></table></div>');
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

    //Adding funcionality

    $('#hide_bar table').toggle(function(){

      var panel = $('#lp_navigation_elem div:first').clone();
      $(panel).attr('id','control');
      $('#learning_path_main').append(panel);

      $('#learning_path_left_zone').hide(50);
      $('#learning_path_right_zone').css('marginLeft','10px');
      $('#hide_bar table').css('backgroundImage','url(../img/hide2.png)').css('backgroundColor','#EEEEEE');
      $('#learning_path_main  #control').css({width: "120px", height: "32px", opacity: "0.4", position: "absolute", top: "0px", left:"15px"});
      $('#learning_path_main  #control').draggable({ iframeFix: true, stack: "#learning_path_right_zone" });
    },function(){
      $('#hide_bar table').css('backgroundImage','url(../img/hide0.png)').css('backgroundColor','#EEEEEE');
      $('#learning_path_right_zone').css('marginLeft','290px');
      $('#learning_path_left_zone').show(50);
      $('#learning_path_main  #control').remove();
    });

  });

})(jQuery);
