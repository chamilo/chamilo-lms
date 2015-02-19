{% include template ~ '/skill/skill_wheel.js.tpl' %}

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
    return '<li><a id="skill_to_select_id_'+skill_id+'" href="#" class="load_wheel" rel="'+skill_id+'">'+skill_name+'</a></li>';
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

    /* When clicking in a course title */
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

    /* change background color */
    $(document).ready(function () {
        $("#celestial").click(function () {
            $("#page-back").css("background","#A9E2F3");
        });
        $("#white").click(function () {
            $("#page-back").css("background","#FFFFFF");
        });
        $("#black").click(function () {
            $("#page-back").css("background","#000000");
        });
        $("#lead").click(function () {
            $("#page-back").css("background","#848484");
        });
        $("#light-yellow").click(function () {
            $("#page-back").css("background","#F7F8E0");
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
    }
});

</script>
<div id="page-back">
    <div class="container-fluid">
        <div class="row-fluid">
            <div class="span3 skill-options">
                <div class="skill-home">
                    <a class="btn btn-large btn-block btn-success" href="{{ _p.web }}user_portal.php">{{ "ReturnToCourseList"|get_lang }}</a>
                </div>
                <div class="skill-profile">
                    <div class="avatar">
                        <img width="100px" src="{{ userInfo.avatar }}" style="text-align: center">
                    </div>
                    <div class="info-user">
                        <h4 class="title-skill">{{ userInfo.complete_name }}</h4>
                        <p>
                            <a href="{{ _p.web_main }}social/skills_ranking.php" class="btn btn-default btn-block" target="_blank">{{ 'YourSkillRankingX' | get_lang | format(ranking) }}</a>
                        </p>
                        {% if mySkills is not empty %}
                            {%for skill in mySkills %}
                                {% if skill.iconThumb is empty %}
                                    <img src="{{ 'award_red.png' | icon(22) }}" alt="{{ skill.name }}" title="{{ skill.name }}">
                                {% else %}
                                    <img src="{{ _p.web_data }}{{ skill.iconThumb }}" alt="{{ skill.name }}" title="{{ skill.name }}">
                                {% endif %}
                            {% endfor %}
                        {% endif %}
                        {% for i in 1..(9 - ranking) %}
                            <img src="{{ 'award_red_na.png' | icon(22) }}">
                        {% endfor %}
                    </div>
                </div>
                <!-- Legend -->
                <div class="legend">
                    <h4 class="title-skill">{{ "Legend"|get_lang }}</h4>
                    <p><span class="label-info">&nbsp;&nbsp;&nbsp;&nbsp;</span> {{ "SkillsYouAcquired"|get_lang }}</p>
                    <p><span class="label-warning">&nbsp;&nbsp;&nbsp;&nbsp;</span> {{ "SkillsYouCanLearn"|get_lang }}</p>
                    <p><span class="label-important">&nbsp;&nbsp;&nbsp;&nbsp;</span> {{ "SkillsSearchedFor"|get_lang }}</p>
                </div>
                <!-- End Legend -->
                <!-- ACCORDION -->
                <div class="accordion" id="accordion2">
                    {% if mySkills is not empty %}
                        <div class="accordion-group">
                            <div class="accordion-heading">
                                <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseOne">
                                    <h4 class="title-skill">{{ 'MySkills'|get_lang }}</h4>
                                </a>
                            </div>
                            <div id="collapseOne" class="accordion-body collapse">
                                <div class="accordion-inner">
                                    <!-- MY SKILLS -->
                                    <div id="my_skills" class="skill-items">
                                        <ul class="skill-winner">
                                            {%for skill in mySkills %}
                                                <li>
                                                    <a class="" rel="{{ skill.id}}" href="#">{{ skill.name }}</a>
                                                </li>
                                            {% endfor %}
                                        </ul>
                                    </div>
                                    <!-- MY SKILLS -->
                                </div>
                            </div>
                        </div>
                    {% endif %}
                    <div class="accordion-group">
                        <div class="accordion-heading">
                            <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseTwo">
                                <h4 class="title-skill">{{ 'GetNewSkills' | get_lang }}</h4>
                            </a>
                        </div>
                        <div id="collapseTwo" class="accordion-body collapse">
                            <div class="accordion-inner">
                                <!-- SEARCH -->
                                <div class="search-skill">
                                    <p>{{ 'EnterTheSkillNameToSearch' | get_lang }}</p>
                                    <form id="skill_search" class="form-search">
                                        <select id="skill_id" name="skill_id" /></select>
                                        <div class="button-skill">
                                            <a class="btn btn-block btn-large btn-danger load_root" rel="0" href="#">{{ "ViewSkillsWheel"|get_lang }}</a>
                                            <!-- <a id="clear_selection" class="btn">{{ "Clear"|get_lang }}</a> -->
                                        </div>
                                        <ul id="skill_holder" class="holder_simple"></ul>
                                    </form>
                                </div>
                                <!-- END SEARCH -->
                                <!-- INFO SKILL -->
                                <div class="section-info-skill">
                                    <h4 class="title-skill">{{ 'SkillInfo'|get_lang }}</h4>
                                    <div id="skill_info"></div>
                                </div>
                                <!-- END INFO SKILL -->
                            </div>
                        </div>
                    </div>
                    <div class="accordion-group">
                        <div class="accordion-heading">
                            <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseThree">
                                <h4 class="title-skill">{{ 'DisplayOptions' | get_lang }}</h4>
                            </a>
                        </div>
                        <div id="collapseThree" class="accordion-body collapse">
                            <div class="accordion-inner">
                                <p>{{ 'ChooseABackgroundColor' | get_lang }}</p>
                                <ul>
                                    <li><a href="#" id="white">{{ 'White' | get_lang }}</a></li>
                                    <li><a href="#" id="black">{{ 'Black' | get_lang }}</a></li>
                                    <li><a href="#" id="celestial">{{ 'LightBlue' }}</a></li>
                                    <li><a href="#" id="lead">{{ 'Gray' | get_lang }}</a></li>
                                    <li><a href="#" id="light-yellow">{{ 'Corn' | get_lang }}</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END ACCORDEON -->
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
    </div>
</div>

<div id="dialog-form" style="">
    <p class="validateTips"></p>
    <form id="add_item" class="form-horizontal" name="form">
        <fieldset>
            <div class="control-group">
                <label class="control-label" for="name">{{ 'Name' | get_lang }}</label>
                <div class="controls">
                    <!--<input type="text" name="name" id="name" class="span4" readonly />-->
                    <p id="name" class="span4">
                    </p>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">{{ 'ShortCode' | get_lang }}</label>
                <div class="controls">
                    <!--<input type="text" name="short_code" id="short_code" class="span2" readonly />-->
                    <p id="short_code" class="span4">
                    </p>
                </div>
            </div>
            <div id="skill_row" class="control-group">
                <label class="control-label" for="name">{{'Parent'|get_lang}}</label>
                <div class="controls">
                    <ul id="skill_edit_holder" class="holder holder_simple">
                    </ul>
                </div>
            </div>
            <div id="gradebook_row" class="control-group">
                <label class="control-label" for="name">{{'Gradebook'|get_lang}}</label>
                <div class="controls">
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
                    <!--<textarea name="description" id="description" class="span4" rows="7" readonly>
                    </textarea>-->
                    <p id="description" class="span4">
                    </p>
                </div>
            </div>
        </fieldset>
    </form>
</div>
