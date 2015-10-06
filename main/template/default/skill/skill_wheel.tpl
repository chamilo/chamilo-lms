{% include template ~ '/skill/skill_wheel.js.tpl' %}
<script>
    /* Skill search input in the left menu */
    function check_skills_sidebar() {
        //Selecting only selected skills
        $("#skill_id option:selected").each(function () {
            var skill_id = $(this).val();
            if (skill_id != "") {
                $.ajax({
                    url: "{{ url }}&a=skill_exists",
                    data: "skill_id=" + skill_id,
                    async: false,
                    success: function (return_value) {
                        if (return_value == 0) {
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
                            $("#skill_id option[value=" + skill_id + "]").remove();

                            //Deleting holder
                            $("#skill_search .holder li").each(function () {
                                if ($(this).attr("rel") == skill_id) {
                                    $(this).remove();
                                }
                            });

                            if ($('#skill_to_select_id_' + skill_id).length == 0) {
                                skill_info = get_skill_info(skill_id);
                                li = fill_skill_search_li(skill_id, skill_info.name);
                                $("#skill_holder").append(li);
                            }
                        }
                    }
                });
            }
        });
    }

    function fill_skill_search_li(skill_id, skill_name, checked) {
        checked_condition = '';
        if (checked == 1) {
            checked_condition = 'checked=checked';
        }
        return '<li>' +
                '<input id="skill_to_select_id_' + skill_id + '" rel="' + skill_id + '" name="' + skill_name + '" class="skill_to_select" ' + checked_condition + ' type="checkbox" value="">' +
                '<a href="#" class="load_wheel" rel="' + skill_id + '">' + skill_name + '</a> ' + 
                '</li>';
    }

    function check_skills_edit_form() {
        //selecting only selected parents
        $("#parent_id option:selected").each(function () {
            var skill_id = $(this).val();
            if (skill_id != "") {
                $.ajax({
                    async: false,
                    url: "{{ url }}&a=skill_exists",
                    data: "skill_id=" + skill_id,
                    success: function (return_value) {
                        if (return_value == 0) {
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

                            $("#skill_edit_holder").append('<li class="bit-box" id="skill_option_' + skill_id + '"> ' + skill.name + '</li>');
                            $("#parent_id").append('<option class="selected" selected="selected" value="' + skill_id + '"></option>');
                        }
                    }
                });
            }
        });
    }

    function check_gradebook() {
        //selecting only selected users
        $("#gradebook_id option:selected").each(function () {
            var gradebook_id = $(this).val();

            if (gradebook_id != "") {
                $.ajax({
                    url: "{{ url }}&a=gradebook_exists",
                    data: "gradebook_id=" + gradebook_id,
                    success: function (return_value) {
                        if (return_value == 0) {
                            alert("{{ 'GradebookDoesNotExist'|get_lang }}");
                            //Deleting select option tag
                            $("#gradebook_id option[value=" + gradebook_id + "]").remove();
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

                            if ($('#gradebook_item_' + gradebook_id).length == 0) {
                                gradebook = get_gradebook_info(gradebook_id);
                                if (gradebook) {
                                    $("#gradebook_holder").append('<li id="gradebook_item_' + gradebook_id + '" class="bit-box"> ' + gradebook.name +
                                        ' <a rel="' + gradebook_id + '" class="closebutton" href="#"></a></li>');
                                }
                            }
                        }
                    }
                });
            }
        });
    }

    function delete_gradebook_from_skill(skill_id, gradebook_id) {
        $.ajax({
            url: url + '&a=delete_gradebook_from_skill&skill_id=' + skill_id + '&gradebook_id=' + gradebook_id,
            async: false,
            success: function (result) {
                //if (result == 1) {
                $('#gradebook_item_' + gradebook_id).remove();
                $("#gradebook_id option").each(function () {
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
            $("#profile_search li").each(function (index) {
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
            skill_list = {'skill_id': skill_list};
            skill_params = $.param(skill_list);

            $.ajax({
                url: url + '&a=profile_matches&' + skill_params,
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
        if ($('#profile_match_item_' + skill_id).length == 0) {
            $('#profile_search').append('<li class="bit-box" id="profile_match_item_' + skill_id + '">' + skill_name +
                ' <a rel="' + skill_id + '" class="closebutton" href="#"></a> </li>');
        } else {
            $('#profile_match_item_' + skill_id).remove();
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

    $(document).ready(function () {
        /* Skill search */

        //Tool tip (in exercises)

        var tip_options = {
            placement: 'right'
        }
        $('.tooltip_skill').tooltip(tip_options);

        /* Skill item list onclick  */
        $("#skill_holder").on("click", "input.skill_to_select", function () {
            skill_id = $(this).attr('rel');
            skill_name = $(this).attr('name');
            add_skill_in_profile_list(skill_id, skill_name);
        });

        /* URL link when searching skills */
        $("#skill_holder").on("click", "a.load_wheel", function (e) {
            e.preventDefault();

            skill_id = $(this).attr('rel');
            skill_to_load_from_get = 0;
            load_nodes(skill_id, main_depth);
        });

        /* URL link when searching skills */
        $("#skill_search").on("click", "a.load_root", function () {
            skill_id = $(this).attr('rel');
            skill_to_load_from_get = 0;
            load_nodes(skill_id, main_depth);
        });

        /* Profile matcher */

        /* Submit button */
        $("#search_profile_form").submit(function () {
            submit_profile_search_form();
            return false;
        });

        $("form#save_profile_form_button").on('submit', function (e) {
            e.preventDefault();

            var profileId = parseInt($('input[name="profile_id"]').val());

            var getProfileInfo = $.getJSON(
                    '{{ url }}',
                    {
                        a: 'get_profile',
                        profile_id: profileId
                    }
            );

            $.when(getProfileInfo).done(function (profileInfo) {
                $("#name_profile").val(profileInfo.name);
                $("#description_profile").val(profileInfo.description);

                $('#frm-save-profile').modal('show');
            });
        });

        /* Close button in profile matcher items */
        $("#profile_search").on("click", "a.closebutton", function () {
            skill_id = $(this).attr('rel');
            $('input[id=skill_to_select_id_' + skill_id + ']').attr('checked', false);
            $('#profile_match_item_' + skill_id).remove();
            submit_profile_search_form();
            toogle_save_profile_form();
        });

        // Fill saved profiles list
        update_my_saved_profiles();

        /* Click in profile */
        $("#saved_profiles").on("click", "li.load_profile", function () {
            profile_id = $(this).attr('rel');
            $('input[name="profile_id"]').val(profile_id);
            $.ajax({
                url: '{{ url }}&a=get_skills_by_profile&profile_id=' + profile_id,
                success: function (json) {
                    skill_list = jQuery.parseJSON(json);

                    $('#profile_search').empty();
                    $('#skill_holder').empty();
                    jQuery.each(skill_list, function (index, skill_id) {
                        skill_info = get_skill_info(skill_id);
                        li = fill_skill_search_li(skill_id, skill_info.name, 1);
                        $("#skill_holder").append(li);
                        add_skill_in_profile_list(skill_id, skill_info.name);
                    });
                    submit_profile_search_form();
                }
            });
        });

        $("#saved_profiles").on('click', 'li.load_profile button.close', function () {
            var $parent = $(this).parent();
            var profileId = $parent.attr('rel');

            $.getJSON('{{ url }}&a=delete_profile', {
                profile: profileId
            }, function (response) {
                if (response.status) {
                    $parent.remove();
                }
            });
        });

        /* Wheel skill popup form */

        /* Close button in gradebook select */
        $("#gradebook_holder").on("click", "a.closebutton", function () {
            gradebook_id = $(this).attr('rel');
            skill_id = $('input[name="id"]').attr('value');
            delete_gradebook_from_skill(skill_id, gradebook_id);
        });

        $("#skill_id").fcbkcomplete({
            json_url: "{{ url }}&a=find_skills",
            cache: false,
            filter_case: false,
            filter_hide: true,
            complete_text: "{{ 'StartToType' | get_lang }}",
            firstselected: true,
            //onremove: "testme",
            onselect: "check_skills_sidebar",
            filter_selected: true,
            newel: true
        });

        load_nodes(0, main_depth);

        function update_my_saved_profiles() {
            $.ajax({
                url: '{{ url }}&a=get_saved_profiles',
                success: function (data) {
                    $("#saved_profiles").html(data);
                }
            });
        }

        /* change background color */
        $("#celestial").click(function () {
            $("#page-back").css("background", "#A9E2F3");
        });
        $("#white").click(function () {
            $("#page-back").css("background", "#FFFFFF");
        });
        $("#black").click(function () {
            $("#page-back").css("background", "#000000");
        });
        $("#lead").click(function () {
            $("#page-back").css("background", "#848484");
        });
        $("#light-yellow").click(function () {
            $("#page-back").css("background", "#F7F8E0");
        });

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

        $('#form-button-edit').on('click', function (e) {
            e.preventDefault();

            if (SkillWheel.currentSkill === null) {
                return;
            }

            window.location.href = "{{ _p.web_main }}admin/skill_edit.php?id=" + SkillWheel.currentSkill.id;
        });

        $('#form-button-create-child').on('click', function (e) {
            e.preventDefault();

            if (SkillWheel.currentSkill === null) {
                return;
            }

            window.location.href = "{{ _p.web_main }}admin/skill_create.php?parent=" + SkillWheel.currentSkill.id;
        });

        $('#form-button-add-to-profile').on('click', function (e) {
            e.preventDefault();

            if (SkillWheel.currentSkill === null) {
                return;
            }

            add_skill_in_profile_list(SkillWheel.currentSkill.id, SkillWheel.currentSkill.name);
        });

        $('#frm-save-profile').on('hidden.bs.modal', function () {
            $("#name_profile").val('');
            $("#description_profile").val('');
            $('input[name="profile_id"]').val(0);
        });

        $('#form-button-save-profile').on('click', function (e) {
            e.preventDefault();

            var saveProfile = $.ajax(
                    '{{ url }}',
                    {
                        data: {
                            a: 'save_profile',
                            name: $("#name_profile").val(),
                            description: $("#description_profile").val(),
                            skill_id: return_skill_list_from_profile_search(),
                            profile: $('input[name="profile_id"]').val()
                        }
                    }
            );

            $.when(saveProfile).done(function (response) {
                if (parseInt(response) === 1) {
                    update_my_saved_profiles();

                    alert("{{ "Saved" | get_lang }}");
                } else {
                    alert("{{ "Error" | get_lang }}");
                }

                $('#frm-save-profile').modal('hide');
            });
        });
    });
</script>
<div id="page-back" class="page-skill">
    <div class="container-fluid">
        <div class="row">

            <div class="col-md-3 skill-options">
                <div class="skill-home">
                    <a class="btn btn-large btn-block btn-success" href="{{ _p.web }}user_portal.php">
                        <i class="fa fa-home"></i> {{ "ReturnToCourseList"|get_lang }}
                    </a>
                </div>
                <!-- ACCORDION -->
                <div class="panel-group" id="accordion2">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <a data-toggle="collapse" data-parent="#accordion2" href="#collapseOne">
                                {{ 'SkillsSearch' | get_lang }}
                            </a>
                        </div>
                        <div id="collapseOne" class="panel-collapse collapse">
                            <div class="panel-body">
                                <!-- SEARCH -->
                                <div class="search-skill">
                                    <p class="text">{{ 'EnterTheSkillNameToSearch' | get_lang }}</p>
                                    <form id="skill_search" class="form-search">
                                        <select id="skill_id" name="skill_id" /></select>
                                        <div class="button-skill">
                                            <a class="btn btn-block btn-default load_root" rel="0" href="#">
                                                <i class="fa fa-eye"></i> {{ "ViewSkillsWheel"|get_lang }}
                                            </a>
                                            <!-- <a id="clear_selection" class="btn btn-danger">{{ "Clear"|get_lang }}</a> -->
                                        </div>
                                        <ul id="skill_holder" class="holder_simple border"></ul>
                                    </form>
                                </div>
                                <!-- END SEARCH -->
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <a data-toggle="collapse" data-parent="#accordion2" href="#collapseTwo">
                                {{ 'ProfileSearch'|get_lang }}
                            </a>
                        </div>
                        <div id="collapseTwo" class="panel-collapse collapse">
                            <div class="panel-body">
                                <!-- SEARCH PROFILE -->
                                <div class="search-profile-skill">

                                    <p class="text">{{ 'WhatSkillsAreYouLookingFor'|get_lang }}</p>
                                    <ul id="profile_search" class="holder holder_simple"></ul>
                                    <form id="search_profile_form" class="form-search">
                                        <button class="btn btn-default btn-block" type="submit">
                                            <i class="fa fa-search"></i> {{ "SearchProfileMatches"|get_lang }}
                                        </button>
                                    </form>
                                    <p class="text">{{ 'IsThisWhatYouWereLookingFor'|get_lang }}</p>
                                    <form id="save_profile_form_button" class="form-search">
                                        <button class="btn btn-default btn-block" type="submit">
                                            <i class="fa fa-floppy-o"></i> {{ "SaveThisSearch"|get_lang }}
                                        </button>
                                    </form>
                                </div>
                                <!-- END SEARCH PROFILE-->
                                <div id="saved_profiles"></div>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <a data-toggle="collapse" data-parent="#accordion2" href="#collapseThree">
                                {{ 'DisplayOptions' | get_lang }}
                            </a>
                        </div>
                        <div id="collapseThree" class="panel-collapse collapse">
                            <div class="panel-body">
                                <p class="text">{{ 'ChooseABackgroundColor' | get_lang }}</p>
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
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <a data-toggle="collapse" data-parent="#accordion2" href="#collapseFour">
                                {{ "Legend"|get_lang }}
                            </a>
                        </div>
                        <div id="collapseFour" class="panel-collapse collapse">
                            <div class="panel-body">
                                <p class="text"><span class="skill-legend-add">&nbsp;&nbsp;&nbsp;&nbsp;</span> {{ "SkillsYouCanLearn"|get_lang }}</p>
                                <p class="text"><span class="skill-legend-search">&nbsp;&nbsp;&nbsp;&nbsp;</span> {{ "SkillsSearchedFor"|get_lang }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END ACCORDEON -->
            </div>
            <div id="wheel_container" class="col-md-9">
                <div id="skill_wheel">
                    <img src="">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="frm-skill" tabindex="-1" role="dialog" aria-labelledby="form-skill-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ "Close" | get_lang }}">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="form-skill-title">{{ "Skill" | get_lang }}</h4>
            </div>
            <div class="modal-body">
                {{ dialogForm }}
            </div>
            <div class="modal-footer">
                <button id="form-button-edit" class="btn btn-primary">
                    <i class="fa fa-edit"></i> {{ "Edit" | get_lang }}
                </button>
                <button id="form-button-create-child" class="btn btn-primary">
                    <i class="fa fa-plus"></i> {{ "CreateChildSkill" | get_lang }}
                </button>
                <button id="form-button-add-to-profile" class="btn btn-primary">
                    <i class="fa fa-check"></i> {{ "AddSkillToProfileSearch" | get_lang }}
                </button>
                <button type="button" class="btn btn-primary" data-dismiss="modal">
                    <i class="fa fa-close"></i> {{ "Close" | get_lang }}
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="frm-save-profile" tabindex="-1" role="dialog" aria-labelledby="form-save-profile-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ "Close" | get_lang }}">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="form-save-profile-title">{{ "SkillProfile" | get_lang }}</h4>
            </div>
            <div class="modal-body">
                {{ saveProfileForm }}
            </div>
            <div class="modal-footer">
                <button id="form-button-save-profile" class="btn btn-primary">
                    <i class="fa fa-save"></i> {{ "Save" | get_lang }}
                </button>
                <button type="button" class="btn btn-primary" data-dismiss="modal">
                    <i class="fa fa-close"></i> {{ "Close" | get_lang }}
                </button>
            </div>
        </div>
    </div>
</div>
