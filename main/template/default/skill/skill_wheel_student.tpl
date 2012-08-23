{% include 'default/skill/skill_wheel.js.tpl' %}

<script>
 
/* Skill search input in the left menu */
function check_skills_sidebar() {
    //Selecting only selected skills
    $("#skill_id option:selected").each(function() {
        var skill_id = $(this).val();                
        if (skill_id != "" ) {
            $.ajax({
                url: "{{ url }}&a=skill_exists", 
                data: "skill_id="+skill_id,
//                async: false, 
                success: function(return_value) {                   
                    if (return_value == 0 ) {
                        alert("{{ 'SkillDoesNotExist'|get_lang }}");                                                
                        //Deleting select option tag
                        $("#skill_id option[value="+skill_id+"]").remove();                    
                       
                        //Deleting holder
                        $("#skill_search .holder li").each(function () {
                            if ($(this).attr("rel") == skill_id) {
                                $(this).remove();
                            }
                        });                        
                    } else {
                        $("#skill_id option[value="+skill_id+"]").remove();
                          
                        //Deleting holder
                        $("#skill_search .holder li").each(function () {
                            if ($(this).attr("rel") == skill_id) {
                                $(this).remove();
                            }
                        });
                        
                        if ($('#skill_to_select_id_'+skill_id).length == 0) {
                            skill_info = get_skill_info(skill_id);                        
                            li = fill_skill_search_li(skill_id, skill_info.name);
                            $("#skill_holder").append(li); 
                        }                        

                    }
                },            
            });                
        }
    });
}

function fill_skill_search_li(skill_id, skill_name, checked) {
    checked_condition = '';
    if (checked == 1) {
        checked_condition = 'checked=checked';
    }    
    return '<li><a href="#" class="load_wheel" rel="'+skill_id+'">'+skill_name+'</a></li>';
}


function load_skill_info(skill_id) {
    $.ajax({
        url: url+'&a=get_skill_course_info&id='+skill_id,
        async: false,        
        success: function(data) {            
            $('#skill_info').html(data);
            return data;
        }
    });
}

$(document).ready(function() {
    /* Skill search */ 
    
    /* Skill item list onclick  */
    $("#skill_holder").on("click", "input.skill_to_select", function() {
        skill_id = $(this).attr('rel');
        skill_name = $(this).attr('name');
        add_skill_in_profile_list(skill_id, skill_name);
    });
   
    
     /* URL link when searching skills */
    $("#skill_holder").on("click", "a.load_wheel", function() {
        skill_id = $(this).attr('rel');
        skill_to_load_from_get = 0;
        load_nodes(skill_id, main_depth);        
        load_skill_info(skill_id);
    });
    
     /* URL link when searching skills */
    $("#skill_search").on("click", "a.load_root", function() {
        skill_id = $(this).attr('rel');
        skill_to_load_from_get = 0;
        load_nodes(skill_id, main_depth);
    });
    
        
    $("#skill_info").on("click", "a.course_description_popup", function() {
        course_code = $(this).attr('rel');        
        $.ajax({
            url: url+'&a=get_course_info_popup&code='+course_code,
            async: false,        
            success: function(data) {                
                $('#course_info').html(data);
                $("#dialog-course-info").dialog({
                     close: function() {     
                        $('#course_info').html('');                
                    }
                });
                $("#dialog-course-info").dialog("open");
            }
        });
    });
        
    
    /* Wheel skill popup form */
    
    /* Close button in gradebook select */
    $("#gradebook_holder").on("click", "a.closebutton", function() {
        gradebook_id = $(this).attr('rel');
        skill_id = $('#id').attr('value');         
        delete_gradebook_from_skill(skill_id, gradebook_id);        
    });    

    $("#skill_id").fcbkcomplete({
        json_url: "{{ url }}&a=find_skills",
        cache: false,
        filter_case: false,
        filter_hide: true,
        complete_text:"{{ 'StartToType' | get_lang }}",
        firstselected: true,
        //onremove: "testme",
        onselect:"check_skills_sidebar",
        filter_selected: true,
        newel: true
    });    
    
    $("#parent_id").fcbkcomplete({
        json_url: "{{ url }}&a=find_skills",
        cache: false,
        filter_case: false,
        filter_hide: true,
        complete_text:"{{ 'StartToType' | get_lang }}",
        firstselected: true,
        //onremove: "testme",
        onselect:"check_skills_edit_form",
        filter_selected: true,
        newel: true
    });    
    
    $("#gradebook_id").fcbkcomplete({
        json_url: "{{ url }}&a=find_gradebooks",
        cache: false,
        filter_case: false,
        filter_hide: true,
        complete_text:"{{ 'StartToType' | get_lang }}",
        firstselected: true,
        //onremove: "testme",
        onselect:"check_gradebook",
        filter_selected: true,
        newel: true
    });

    //Open dialog
    $("#dialog-form").dialog({
        autoOpen: false,
        modal   : true, 
        width   : 600, 
        height  : 550
    });
    
    //Open dialog
    $("#dialog-course-info").dialog({
        autoOpen: false,
        modal   : true, 
        width   : 550, 
        height  : 250
    });    
        
    load_nodes(0, main_depth);    
    
    function open_popup(skill_id, parent_id) {
        //Cleaning selected        
        $("#gradebook_id").find('option').remove();        
        $("#parent_id").find('option').remove();
        
        $("#gradebook_holder").find('li').remove();
        $("#skill_edit_holder").find('li').remove();
                
        var skill = false;
        if (skill_id) {
            skill = get_skill_info(skill_id);
        }        
        
        var parent = false;
        if (parent_id) {
            parent = get_skill_info(parent_id);
        }
        
        if (skill) {
            var parent_info = get_skill_info(skill.extra.parent_id);
            
            $("#id").attr('value',   skill.id);
            $("#name").attr('value', skill.name);
            $("#short_code").attr('value', skill.short_code);                        
            $("#description").attr('value', skill.description);                
            
            //Filling parent_id                        
            $("#parent_id").append('<option class="selected" value="'+skill.extra.parent_id+'" selected="selected" >');            
            
            $("#skill_edit_holder").append('<li class="bit-box">'+parent_info.name+'</li>');
            
            //Filling the gradebook_id
            jQuery.each(skill.gradebooks, function(index, data) {                    
                $("#gradebook_id").append('<option class="selected" value="'+data.id+'" selected="selected" >');
                $("#gradebook_holder").append('<li id="gradebook_item_'+data.id+'" class="bit-box">'+data.name+' <a rel="'+data.id+'" class="closebutton" href="#"></a> </li>');    
            });            
            
            $("#dialog-form").dialog({
                buttons: {
                     "{{ "Edit"|get_lang }}" : function() {
                         var params = $("#add_item").find(':input').serialize();
                         add_skill(params);                     
                      },                                    
                      /*"{{ "Delete"|get_lang }}" : function() {
                      },*/
                      "{{ "CreateChildSkill"|get_lang }}" : function() {                          
                          open_popup(0, skill.id);

                      },
                      "{{ "AddSkillToProfileSearch"|get_lang }}" : function() {                          
                          add_skill_in_profile_list(skill.id, skill.name);
                      }
                },
                close: function() {     
                    $("#name").attr('value','');
                    $("#description").attr('value', '');
                    //Redirect to the main root
                    load_nodes(0, main_depth);
                                 
                }
            });
            
            $("#dialog-form").dialog("open");            
        }  
        
        if (parent) {
            $("#id").attr('value','');
            $("#name").attr('value', '');
            $("#short_code").attr('value', '');
            $("#description").attr('value', '');
            
            //Filling parent_id                        
            $("#parent_id").append('<option class="selected" value="'+parent.id+'" selected="selected" >');            
            
            $("#skill_edit_holder").append('<li class="bit-box">'+parent.name+'</li>');
            
            //Filling the gradebook_id
            jQuery.each(parent.gradebooks, function(index, data) {                    
                $("#gradebook_id").append('<option class="selected" value="'+data.id+'" selected="selected" >');
                $("#gradebook_holder").append('<li id="gradebook_item_'+data.id+'" class="bit-box">'+data.name+' <a rel="'+data.id+'" class="closebutton" href="#"></a> </li>');    
            });            
            
            $("#dialog-form").dialog({
                buttons: {
                     "{{ "Save"|get_lang }}" : function() {
                         var params = $("#add_item").find(':input').serialize();
                         add_skill(params);                     
                      }
           
                },
                close: function() {     
                    $("#name").attr('value', '');
                    $("#description").attr('value', '');
 	                   load_nodes(0, main_depth);              
                }
            });
            $("#dialog-form").dialog("open");        
        }
    }
});
</script>


<div class="container-fluid">
    <div class="row-fluid">
        
        <div class="span3">
            <div class="well">
                <h3>{{ 'MySkills'|get_lang }}</h3>
                <hr>
                
                <div id="my_skills">                    
                </div>
                
                <h3>{{ 'GetNewSkills'|get_lang }}</h3>
                <hr>
                
                <form id="skill_search" class="form-search">
                    <select id="skill_id" name="skill_id" />
                    </select>
                    <br /><br />
                    <div class="btn-group">
                        <a class="btn load_root" rel="1" href="#">{{ "SkillRoot"|get_lang }}</a>
                        <!-- <a id="clear_selection" class="btn">{{ "Clear"|get_lang }}</a> -->	
                    </div>
                    <ul id="skill_holder" class="holder holder_simple">
                    </ul>
                </form>
                
                
                <h3>{{ 'SkillInfo'|get_lang }}</h3>
                <hr>                
                <div id="skill_info">
                </div>
                
                <br />
                <h3>{{ "Legend"|get_lang }}</h3>                
                <span class="label label-warning">{{ "SkillsYouCanLearn"|get_lang }}</span><br />
                <span class="label label-important">{{ "SkillsSearchedFor"|get_lang }}</span><br />                
                <span class="label label-info">{{ "SkillsYouAcquired"|get_lang }}</span><br />
                
                
            </div>                
        </div>
            
        <div id="wheel_container" class="span9">
            <div id="skill_wheel">
                <img src="">
            </div>
        </div>
</div>

        
<div id="dialog-course-info" style="display:none;">        
    <div id="course_info">
    </div>    
</div>