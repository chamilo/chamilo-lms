
// En este archivo se agregaran funcionalidades dependientes de jquery al
// Learning path

(function($){
  $(document).ready(function() {

    //Agregar botón que ocultará el panel y mostrará un mini panel
    $('#learning_path_header table').attr('width','100%');
    $('#learning_path_left_zone table td:first').attr('width','32');
    $('#learning_path_header td').attr('align','left');
    $('#learning_path_header td:last').attr('align','rigth');
    $('#learning_path_header td:last').after('<td width="16px"> <a class="hide" href="#"><img src="../img/first.png" alt="Hide" /></a> </td>');

    // Se asocia funcionalidad al botón para mostrar ocultar learning path
    $('.hide').click(function(){

      var panel = $('#lp_navigation_elem div:first').clone();
      $(panel).attr('id','control');
      $('#learning_path_main').append(panel);

      $('#learning_path_left_zone').hide(250);
      $('#learning_path_right_zone').css('marginLeft','0px');

      // Mostrar controles
      $('#learning_path_main  #control table').after('<td width="16px"> <a class="show" href="#"><img src="../img/first.png" alt="Show" /></a> </td>');
      $('#learning_path_main  #control').css({width: "120px", height: "32px", opacity: "0.4", zindex: "-1", position: "absolute", top: "0px", left:"15px"});
      $('#learning_path_main  #control').draggable();

      // Muestra panel y destruye panel

      $('.show').click(function(){
      $('#learning_path_right_zone').css('marginLeft','282px');
      $('#learning_path_left_zone').show(250);
      $('#learning_path_main  #control').remove();
      });

    });




  });
})(jQuery);
