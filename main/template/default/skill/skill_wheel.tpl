{% include 'default/skill/skill_wheel.js.tpl' %}

<script>

function add_skill(params) {
    $.ajax({
        async: false,
        url: url+'&a=add&'+params,
        success:function(my_id) {
            //Close dialog
            $("#dialog-form").dialog("close");                                      
        }                           
    });
}
    
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
                        //$("#skill_id option[value="+skill_id+"]").remove();                    
                        $("#skill_id").empty();
                       
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
    return '<li><input id="skill_to_select_id_'+skill_id+'" rel="'+skill_id+'" name="'+skill_name+'" class="skill_to_select" '+checked_condition+' type="checkbox" value=""> <a href="#" class="load_wheel" rel="'+skill_id+'">'+skill_name+'</a></li>';
}
 
function check_skills_edit_form() {
    //selecting only selected parents
    $("#parent_id option:selected").each(function() {
        var skill_id = $(this).val();        
        if (skill_id != "" ) {
            $.ajax({ 
                async: false,
                url: "{{ url }}&a=skill_exists", 
                data: "skill_id="+skill_id,
                success: function(return_value) {                    
                    if (return_value == 0 ) {                        
                        alert("{{ 'SkillDoesNotExist'|get_lang }}");
                        
                        //Deleting select option tag
                        $("#parent_id").find('option').remove();
                        //Deleting holder
                        $("#skill_row .holder li").each(function () {
                            if ($(this).attr("rel") == skill_id) {
                                $(this).remove();
                            }
                        });                        
                    } else {
                        $("#parent_id").empty();                        
                        $("#skill_edit_holder").find('li').remove();
                        
                        //Deleting holder
                        $("#skill_row .holder li").each(function () {
                            if ($(this).attr("rel") == skill_id) {
                                $(this).remove();
                            }
                        });
                        
                        skill = get_skill_info(skill_id);                        
                                                                         
                        $("#skill_edit_holder").append('<li class="bit-box" id="skill_option_'+skill_id+'"> '+skill.name+'</li>');
                        $("#parent_id").append('<option class="selected" selected="selected" value="'+skill_id+'"></option>');
                    }
                },            
            });                
        }
    });
}

function check_gradebook() {
    //selecting only selected users
    $("#gradebook_id option:selected").each(function() {
        var gradebook_id = $(this).val();
        
        if (gradebook_id != "" ) {
            $.ajax({ 
                url: "{{ url }}&a=gradebook_exists", 
                data: "gradebook_id="+gradebook_id,
                success: function(return_value) {                    
                    if (return_value == 0 ) {
                        alert("{{ 'GradebookDoesNotExist'|get_lang }}");                                                
                        //Deleting select option tag
                        $("#gradebook_id option[value="+gradebook_id+"]").remove();                        
                        //Deleting holder
                        $("#gradebook_row .holder li").each(function () {
                            if ($(this).attr("rel") == gradebook_id) {
                                $(this).remove();
                            }
                        });                        
                    } else {                        
                        //Deleting holder                        
                        $("#gradebook_row .holder li").each(function () {
                            if ($(this).attr("rel") == gradebook_id) {
                                $(this).remove();
                            }
                        });
                        
                        if ($('#gradebook_item_'+gradebook_id).length == 0) {
                            gradebook = get_gradebook_info(gradebook_id);                            
                            if (gradebook) {
                                $("#gradebook_holder").append('<li id="gradebook_item_'+gradebook_id+'" class="bit-box"> '+gradebook.name+' <a rel="'+gradebook_id+'" class="closebutton" href="#"></a></li>');
                            }                            
                        }
                    }
                },            
            });                
        }
    });
}

function delete_gradebook_from_skill(skill_id, gradebook_id) {
    $.ajax({
        url: url+'&a=delete_gradebook_from_skill&skill_id='+skill_id+'&gradebook_id='+gradebook_id,
        async: false,        
        success: function(result) {
            //if (result == 1) {
                $('#gradebook_item_'+gradebook_id).remove();
                $("#gradebook_id option").each(function() {
                    if ($(this).attr("value") == gradebook_id) {
                        $(this).remove();
                    }
                });
            //}
        }
    });
}

function return_skill_list_from_profile_search() {
    var skill_list = {};

    if ($("#profile_search li").length != 0) {            
        $("#profile_search li").each(function(index) {
            id = $(this).attr("id").split('_')[3];            
            if (id) {                         
                skill_list[index] = id;
            }
        }); 
    }
    return skill_list;
}

function submit_profile_search_form() {
    $("#skill_wheel").remove();
    
    var skill_list = return_skill_list_from_profile_search();

    if (skill_list.length != 0) {
        skill_list = { 'skill_id' : skill_list };
        skill_params = $.param(skill_list);        

        $.ajax({
            url: url+'&a=profile_matches&'+skill_params,
            async: false,        
            success: function (html) {
                //users = jQuery.parseJSON(users);
                $('#wheel_container').html(html);

            }
        });
    }
    //return skill;
}

function add_skill_in_profile_list(skill_id, skill_name) {
    if ($('#profile_match_item_'+skill_id).length == 0 ) {        
        $('#profile_search').append('<li class="bit-box" id="profile_match_item_'+skill_id+'">'+skill_name+'  <a rel="'+skill_id+'" class="closebutton" href="#"></a> </li>');        
    } else {            
        $('#profile_match_item_'+skill_id).remove();
    }
    toogle_save_profile_form();
}

function toogle_save_profile_form() {
    //Hiding showing the save this search    
    if ($('#profile_search li').length == 0) {
        $('#profile-options-container').hide();    
    } else {
        $('#profile-options-container').show();
    }
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
    });
    
     /* URL link when searching skills */
    $("#skill_search").on("click", "a.load_root", function() {
        skill_id = $(this).attr('rel');
        skill_to_load_from_get = 0;
        load_nodes(skill_id, main_depth);
    });
    
    /*$("#skill_search").on("click", "a#clear_selection", function() {
        
    });*/
    
    
    /* Profile matcher */
            
    /* Submit button */
    $("#search_profile_form").submit(function() {
        submit_profile_search_form();
        return false;
    });
    
    $("#save_profile_form_button").submit(function() {         
        open_save_profile_popup();
        return false;
    });
    
    /* Close button in profile matcher items */
    $("#profile_search").on("click", "a.closebutton", function() {
        skill_id = $(this).attr('rel');     
        $('input[id=skill_to_select_id_'+skill_id+']').attr('checked', false);
        $('#profile_match_item_'+skill_id).remove();
        submit_profile_search_form();
        toogle_save_profile_form();
    });
    
    //Fill saved profiles list
    update_my_saved_profiles();
    
    /* Click in profile */
    $("#saved_profiles").on("click", "a.load_profile", function() {
        profile_id = $(this).attr('rel');        
        $.ajax({
           url: '{{ url }}&a=get_skills_by_profile&profile_id='+profile_id,
           success:function(json) {
               skill_list = jQuery.parseJSON(json); 
               
               $('#profile_search').empty();
               $('#skill_holder').empty();               
               jQuery.each(skill_list, function(index, skill_id) {                    
                    skill_info = get_skill_info(skill_id);                        
                    li = fill_skill_search_li(skill_id, skill_info.name, 1);
                    $("#skill_holder").append(li);
                    add_skill_in_profile_list(skill_id, skill_info.name);
               });               
               submit_profile_search_form();              
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

    //Skill popup (edit, create child... )
    $("#dialog-form").dialog({
        autoOpen: false,
        modal   : true, 
        width   : 600, 
        height  : 580
    });
    
    //Save search profile dialog
    $("#dialog-form-profile").dialog({
        autoOpen: false,
        modal   : true, 
        width   : 500, 
        height  : 400
    });
        
    load_nodes(0, main_depth);
    
    function open_save_profile_popup() {
        $("#dialog-form-profile").dialog({
            buttons: {
                "{{ "Save"|get_lang }}" : function() {
                    var params = $("#save_profile_form").serialize();
                    var skill_list = return_skill_list_from_profile_search();                    
                    skill_list = { 'skill_id' : skill_list };
                    skill_params = $.param(skill_list);
        
                    $.ajax({
                        url: '{{ url }}&a=save_profile&'+params+'&'+skill_params,
                        success:function(data) {
                            if (data == 1 ) {
                                update_my_saved_profiles();
                                alert("{{ "Saved"|get_lang }}");
                            } else {
                                alert("{{ "Error"|get_lang }}");
                            }
                            
                            $("#dialog-form-profile").dialog("close");                            
                            $("#name").attr('value', '');
                            $("#description").attr('value', '');                             
                         }                           
                     });
                  }
            },
            close: function() {     
                $("#name").attr('value', '');
                $("#description").attr('value', '');                
            }
        });
        $("#dialog-form-profile").dialog("open");
    }
    
    function update_my_saved_profiles() {
        $.ajax({
           url: '{{ url }}&a=get_saved_profiles',
           success:function(data) {
               $("#saved_profiles").html(data);
            }                           
        });
    }

    /* Generated random colour */
    /*
    function colour(d) {
        
        if (d.children) {
            // There is a maximum of two children!
            var colours = d.children.map(colour),
            a = d3.hsl(colours[0]),
            b = d3.hsl(colours[1]);
            // L*a*b* might be better here...
            return d3.hsl((a.h + b.h) / 2, a.s * 1.2, a.levels_to_show / 1.2);
        }        
        return d.colour || "#fff";
    }*/
});
</script>

<div class="container-fluid">
    <div class="row-fluid">
        
        <div class="span3">
            <div class="well sidebar-nav-skill-wheel">
                <div class="page-header">
                    <h3>{{ 'Skills'|get_lang }}</h3>
                </div>
                
                <form id="skill_search" class="form-search">
                    <select id="skill_id" name="skill_id" />
                    </select>
                    <br /><br />
                    <div class="btn-group">
                        <a class="btn load_root" rel="0" href="#">{{ "SkillRoot"|get_lang }}</a>
                        <!-- <a id="clear_selection" class="btn">{{ "Clear"|get_lang }}</a> -->	
                    </div>
                    <ul id="skill_holder" class="holder holder_simple">
                    </ul>
                </form>
                
                <div class="page-header">
                    <h3>{{ 'ProfileSearch'|get_lang }}</h3>
                </div>
                {{ 'WhatSkillsAreYouLookingFor'|get_lang }}
                
                <ul id="profile_search" class="holder holder_simple">
                </ul>
                
                <form id="search_profile_form" class="form-search">
                    <input class="btn" type="submit" value="{{ "SearchProfileMatches"|get_lang }}">
                </form>
                
                <div id="profile-options-container" style="display:none">                
                    {{ 'IsThisWhatYouWereLookingFor'|get_lang }}
                    <form id="save_profile_form_button" class="form-search">
                        <input class="btn" type="submit" value="{{ "SaveThisSearch"|get_lang }}">
                    </form>                  
                </div>                
                 
                <div id="saved_profiles">
                </div>
                
                <div class="page-header">            
                    <h3>{{ "Legend"|get_lang }}</h3>                
                </div>
                <span class="label label-warning">{{ "SkillsYouCanLearn"|get_lang }}</span><br /><br />
                <span class="label label-important">{{ "SkillsSearchedFor"|get_lang }}</span><br />                
            </div>                
        </div>
            
        <div id="wheel_container" class="span9">
            <div id="skill_wheel">
                <img src="">
            </div>
        </div>
</div>

<div id="dialog-form" style="display:none; z-index:9001;">
    <p class="validateTips"></p>
    <form id="add_item" class="form-horizontal"  name="form">
        <fieldset>
            <input type="hidden" name="id" id="id"/>
            <div class="control-group">            
                <label class="control-label" for="name">{{ 'Name' | get_lang }}</label>            
                <div class="controls">
                    <input type="text" name="name" id="name" class="span4" />             
                </div>
            </div>
            
            <div class="control-group">            
                <label class="control-label">{{ 'ShortCode' | get_lang }}</label>            
                <div class="controls">
                    <input type="text" name="short_code" id="short_code" class="span2" />             
                </div>
            </div>

             <div id="skill_row" class="control-group">            
                <label class="control-label" for="name">{{'Parent'|get_lang}}</label>            
                <div class="controls">
                    <select id="parent_id" name="parent_id" />
                    </select>
                    <ul id="skill_edit_holder" class="holder holder_simple">
                    </ul>
                </div>
            </div>
            
            <div id="gradebook_row" class="control-group">            
                <label class="control-label" for="name">{{'Gradebook'|get_lang}}</label>            
                <div class="controls">
                    <select id="gradebook_id" name="gradebook_id" multiple="multiple"/>
                    </select>             
                        
                    <ul id="gradebook_holder" class="holder holder_simple">
                    </ul>
                        
                    <span class="help-block">
                    {{ 'WithCertificate'|get_lang }}
                    </span>           
                </div>
            </div>
            
            <div class="control-group">            
                <label class="control-label" for="name">{{ 'Description'|get_lang }}</label>            
                <div class="controls">
                    <textarea name="description" id="description" class="span4" rows="7"></textarea>
                </div>
            </div>  
        </fieldset>
    </form>     
</div>
        
        
<div id="dialog-form-profile" style="display:none;">    
    <form id="save_profile_form" class="form-horizontal" name="form">       
        <fieldset>
            <div class="control-group">            
                <label class="control-label" for="name">{{"Name"|get_lang}}</label>            
                <div class="controls">
                    <input type="text" name="name" id="name" size="40" />             
                </div>
            </div>        
            <div class="control-group">            
                <label class="control-label" for="name">{{"Description"|get_lang}}</label>            
                <div class="controls">
                    <textarea name="description" id="description" class="span2"  rows="7"></textarea>
                </div>
            </div>  
        </fieldset>
    </form>    
</div>