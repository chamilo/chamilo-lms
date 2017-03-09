/* For licensing terms, see /license.txt */
/**
 * JS library for the Chamilo sepe plugin
 * @package chamilo.plugin.sepe
 */
$(document).ready(function () {
    $("#borrar_datos_identificativos").click(function (e) {
		e.preventDefault();
        e.stopPropagation();
        if(confirm("Confirme si desea borrar todos los datos identificativos del centro y las acciones formativas creadas")){
			$.post("function.php", {tab: "borra_datos_centro"},
            function (data) {
                if (data.status == "false") {
                    alert(data.content);
                } else {
					alert(data.content);
                    location.reload();
                }
            }, "json");
		}
    });
	
	$("#borrar_accion_formativa").click(function (e) {
		e.preventDefault();
        e.stopPropagation();
		vcod = $("#cod_action").val();
        if(confirm("Confirme si desea borrar la acción formativa y todos los datos almacenados.")){
			$.post("function.php", {tab: "borra_accion_formativa", cod:vcod},
            function (data) {
                if (data.status == "false") {
                    alert(data.content);
                } else {
					window.location.replace("listado-acciones-formativas.php");
                    //location.reload();
                }
            }, "json");
		}
    });
	
	$(".del_specialty").click(function(e){
		e.preventDefault();
        e.stopPropagation();
		vcod = $(this).prop("id");
        if(confirm("Confirme si desea borrar la especialidad de la acción formativa y todos los datos de centros  almacenados.")){
			$.post("function.php", {tab: "borra_especialidad_accion", cod:vcod},
            function (data) {
                if (data.status == "false") {
                    alert(data.content);
                } else {
					alert(data.content);
                    location.reload();
                }
            }, "json");
		}
	});
	
	$(".del_classroom").click(function(e){
		e.preventDefault();
        e.stopPropagation();
		vcod = $(this).prop("id");
        if(confirm("Confirme si desea borrar el centro presencial de la especialidad de la acción formativa.")){
			$.post("function.php", {tab: "borra_especialidad_classroom", cod:vcod},
            function (data) {
                if (data.status == "false") {
                    alert(data.content);
                } else {
					alert(data.content);
                    location.reload();
                }
            }, "json");
		}
	});   
	
	$(".del_tutor").click(function(e){
		e.preventDefault();
        e.stopPropagation();
		vcod = $(this).prop("id");
        if(confirm("Confirme si desea borrar los datos del tutor de la especialidad de la acción formativa.")){
			$.post("function.php", {tab: "borra_especialidad_tutor", cod:vcod},
            function (data) {
                if (data.status == "false") {
                    alert(data.content);
                } else {
					alert(data.content);
                    location.reload();
                }
            }, "json");
		}
	});
	
	$(".del_participant").click(function(e){
		e.preventDefault();
        e.stopPropagation();
		vcod = $(this).prop("id");
        if(confirm("Confirme si desea borrar el participante de la acción formativa y todos los datos almacenados.")){
			$.post("function.php", {tab: "borra_participante_accion", cod:vcod},
            function (data) {
                if (data.status == "false") {
                    alert(data.content);
                } else {
					alert(data.content);
                    location.reload();
                }
            }, "json");
		}
	});
	
	$(".del_specialty_participant").click(function(e){
		e.preventDefault();
        e.stopPropagation();
		vcod = $(this).prop("id");
        if(confirm("Confirme si desea borrar la especialidad del participante.")){
			$.post("function.php", {tab: "borra_especialidad_participante", cod:vcod},
            function (data) {
                if (data.status == "false") {
                    alert(data.content);
                } else {
					alert(data.content);
                    location.reload();
                }
            }, "json");
		}
	});
	
	$(".asignar_action_formativa").click(function(e){
		e.preventDefault();
        e.stopPropagation();
		vcourse = $(this).prop("id");
		vaction = $(this).parent().prev().children().val();
		if(vaction != ''){
			$.post("function.php", {tab: "asignar_accion", cod_course:vcourse, cod_action:vaction},
			   function (data) {
				  if (data.status == "false") {
					  alert(data.content);
				  } else {
					  location.reload();
				  }
			   }, "json");
		}else{
			alert("Seleccione una accion formativa del desplegable");	
		}
	});
	
	$(".desvincular_accion").click(function(e){
		e.preventDefault();
        e.stopPropagation();
		vcod = $(this).prop("id");
		$.post("function.php", {tab: "desvincular_action", cod:vcod},
		   function (data) {
			  if (data.status == "false") {
				  alert(data.content);
			  } else {
				  location.reload();
			  }
		   }, "json");
	});
	
	$(".del_action_formativa").click(function(e){
		e.preventDefault();
        e.stopPropagation();
		vcod = $(this).prop("id").substr(3);
		if(confirm("Confirme si desea borrar la acci\u00F3n formativa y desvincular del curso actual")){
			$.post("function.php", {tab: "borra_accion_formativa", cod:vcod},
		   	function (data) {
			  if (data.status == "false") {
				  alert(data.content);
			  } else {
				  location.reload();
			  }
		   }, "json");
		}
	});
	
	$("#slt_user_existente").change(function(){
		if($(this).val() == "NO"){
			$("#box_datos_tutor").show();
			$("#box_listado_tutores").hide();
		}else{
			$("#box_listado_tutores").show();
			$("#box_datos_tutor").hide();
		}
	});
	    
	$(".info_tutor").click(function(e){
		e.preventDefault();
        e.stopPropagation();
		$(this).parent().parent().next().toggle("slow");
	});
	
	$("#slt_centro_existente").change(function(){
		if($(this).val() == "NO"){
			$("#box_datos_centro").show();
			$("#box_listado_centros").hide();
		}else{
			$("#box_listado_centros").show();
			$("#box_datos_centro").hide();
		}
	});
	
	$('form[name="form_participant_action"] input[type="submit"]').click(function(e){
		e.preventDefault();
        e.stopPropagation();
		if($('#cod_user_chamilo').val() == ''){
			alert("Debe indicar un usuario de chamilo del curso con el que corresponda");
		}else{
			$('form[name="form_participant_action"]').submit();
		}
	});
	
	$("#generar_key_sepe").click(function(e){
		e.preventDefault();
        e.stopPropagation();
		$.post("function.php", {tab: "generar_api_key_sepe"},
		   	function (data) {
			  if (data.status == "false") {
				  alert(data.content);
			  } else {
				  $("#input_key").val(data.content);
			  }
		   }, "json");
	});
	
});

