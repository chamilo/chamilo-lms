/* For licensing terms, see /license.txt */
/**
 * JS library for the Chamilo sepe plugin
 * @package chamilo.plugin.sepe
 */
$(document).ready(function () {
    $("#delete-center-data").click(function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (confirm($('#confirmDeleteCenterData').val())) {
            $.post("function.php", {tab: "delete_center_data"},
            function (data) {
                if (data.status == 'false') {
                    alert(data.content);
                } else {
                    alert(data.content);
                    location.reload();
                }
            }, "json");
        }
    });
    
    $("#delete-action").click(function (e) {
        e.preventDefault();
        e.stopPropagation();
        var actionId = $("#action_id").val();
        if (confirm($('#confirmDeleteAction').val())) {
            $.post("function.php", {tab: "delete_action", id:actionId},
            function (data) {
                if (data.status == 'false') {
                    alert(data.content);
                } else {
                    window.location.replace("formative-actions-list.php");
                }
            }, "json");
        }
    });
    
    $(".delete-specialty").click(function(e){
        e.preventDefault();
        e.stopPropagation();
        iid = $(this).prop("id");
        if (confirm($('#confirmDeleteSpecialty').val())) {
            $.post("function.php", {tab: "delete_specialty", id:iid},
            function (data) {
                if (data.status == 'false') {
                    alert(data.content);
                } else {
                    alert(data.content);
                    location.reload();
                }
            }, "json");
        }
    });
    
    $(".delete-classroom").click(function(e){
        e.preventDefault();
        e.stopPropagation();
        iid = $(this).prop("id");
        if (confirm($('#confirmDeleteClassroom').val())) {
            $.post("function.php", {tab: "delete_classroom", id:iid},
            function (data) {
                if (data.status == 'false') {
                    alert(data.content);
                } else {
                    alert(data.content);
                    location.reload();
                }
            }, "json");
        }
    });   
    
    $(".delete-tutor").click(function(e){
        e.preventDefault();
        e.stopPropagation();
        iid = $(this).prop("id");
        if (confirm($('#confirmDeleteTutor').val())) {
            $.post("function.php", {tab: "delete_tutor", id:iid},
            function (data) {
                if (data.status == 'false') {
                    alert(data.content);
                } else {
                    alert(data.content);
                    location.reload();
                }
            }, "json");
        }
    });
    
    $(".delete-participant").click(function(e){
        e.preventDefault();
        e.stopPropagation();
        iid = $(this).prop("id");
        if (confirm($('#confirmDeleteParticipant').val())) {
            $.post("function.php", {tab: "delete_participant", id:iid},
            function (data) {
                if (data.status == 'false') {
                    alert(data.content);
                } else {
                    alert(data.content);
                    location.reload();
                }
            }, "json");
        }
    });
    
    $(".delete-specialty-participant").click(function(e){
        e.preventDefault();
        e.stopPropagation();
        iid = $(this).prop("id");
        if (confirm($("#confirmDeleteParticipantSpecialty").val())) {
            $.post("function.php", {tab: "delete_specialty_participant", id:iid},
            function (data) {
                if (data.status == 'false') {
                    alert(data.content);
                } else {
                    alert(data.content);
                    location.reload();
                }
            }, "json");
        }
    });
    
    $(".assign_action").click(function(e){
        e.preventDefault();
        e.stopPropagation();
        vcourse = $(this).prop("id");
        vaction = $(this).parent().prev().children().val();
        if (vaction != '') {
            $.post("function.php", {tab:"assign_action", course_id:vcourse, action_id:vaction},
               function (data) {
                  if (data.status == 'false') {
                      alert(data.content);
                  } else {
                      location.reload();
                  }
               }, "json");
        } else {
            alert($("#alertAssignAction").val());    
        }
    });
    
    $(".unlink-action").click(function(e){
        e.preventDefault();
        e.stopPropagation();
        iid = $(this).prop("id");
        $.post("function.php", {tab: "unlink_action", id:iid},
           function (data) {
              if (data.status == 'false') {
                  alert(data.content);
              } else {
                  location.reload();
              }
           }, "json");
    });
    
    $(".delete-action").click(function(e){
        e.preventDefault();
        e.stopPropagation();
        iid = $(this).prop("id").substr(16);
        if (confirm($('#confirmDeleteUnlinkAction').val())) {
            $.post("function.php", {tab: "delete_action", id:iid},
               function (data) {
              if (data.status == 'false') {
                  alert(data.content);
              } else {
                  location.reload();
              }
           }, "json");
        }
    });
    
    $("#slt_user_exists").change(function(){
        if ($(this).val() == "0") {
            $("#tutor-data-layer").show();
            $("#tutors-list-layer").hide();
        } else {
            $("#tutors-list-layer").show();
            $("#tutor-data-layer").hide();
        }
    });
        
    $(".info_tutor").click(function(e){
        e.preventDefault();
        e.stopPropagation();
        $(this).parent().parent().next().toggle("slow");
    });
    
    $("#slt_centers_exists").change(function(){
        if ($(this).val() == "0") {
            $("#center-data-layer").show();
            $("#centers-list-layer").hide();
        } else {
            $("#centers-list-layer").show();
            $("#center-data-layer").hide();
        }
    });
    
    $('form[name="form_participant_action"] input[type="submit"]').click(function(e){
        e.preventDefault();
        e.stopPropagation();
        if ($('#platform_user_id').val() == '') {
            alert($("#alertSelectUser").val());
        } else {
            $('form[name="form_participant_action"]').submit();
        }
    });
    
    $("#key-sepe-generator").click(function(e){
        e.preventDefault();
        e.stopPropagation();
        $.post("function.php", {tab: "key_sepe_generator"},
        function (data) {
            if (data.status == 'false') {
                alert(data.content);
            } else {
                $("#input_key").val(data.content);
            }
        }, "json");
    });
    
});

