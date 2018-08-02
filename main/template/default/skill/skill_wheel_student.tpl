{% include 'skill/skill_wheel.js.tpl'|get_template %}
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
//                async: false,
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
        return '\
            <tr>\n\
                <td>' + skill_name + '</td>\n\
                <td class="text-right">\n\
                    <button type="button" id="skill_to_select_id_' + skill_id + '" class="btn btn-warning btn-sm load_wheel" data-id="' + skill_id + '" title="{{ 'PlaceOnTheWheel'|get_lang }}" aria-label="{{ 'PlaceOnTheWheel'|get_lang }}">\n\
                        <span class="fa fa-crosshairs fa-fw" aria-hidden="true"></span>\n\
                    </button>\n\
                </td>\n\
            </tr>';
    }

    function load_skill_info(skill_id) {
        $.ajax({
            url: url + '&a=get_skill_course_info&id=' + skill_id,
            async: false,
            success: function (data) {
                $('#skill_info').html(data);
                return data;
            }
        });
    }

    $(document).ready(function () {
        /* Skill search */

        /* Skill item list onclick  */
        $("#skill_holder").on("click", "input.skill_to_select", function () {
            skill_id = $(this).attr('rel');
            skill_name = $(this).attr('name');
            add_skill_in_profile_list(skill_id, skill_name);
        });

        /* URL link when searching skills */
        $("#skill_holder").on("click", "button.load_wheel", function () {
            skill_id = $(this).data('id') || 0;
            skill_to_load_from_get = 0;
            load_nodes(skill_id, main_depth);
            load_skill_info(skill_id);
        });

        /* URL link when searching skills */
        $("a.load_root").on("click", function (e) {
            e.preventDefault();

            skill_id = $(this).attr('rel');
            skill_to_load_from_get = 0;
            load_nodes(skill_id, main_depth);
        });

        /* When clicking in a course title */
        $("#skill_info").on("click", "a.course_description_popup[rel]", function (e) {
            e.preventDefault();

            var getCourseInfo = $.ajax(url, {
                    data: {
                        a: 'get_course_info_popup',
                        code: $(this).attr('rel')
                    }
                }
            );

            $.when(getCourseInfo).done(function (response) {
                $('#frm-course-info').find('.modal-body').html(response);
                $('#frm-course-info').modal('show');
            });
        });

        /* change background color */
        $('#skill-change-background-options li a').on('click', function (e) {
            e.preventDefault();

            var newBackgroundColor = $(this).data('color') || '#FFF';

            $("#page-back").css("background", newBackgroundColor);
        });

        /* Wheel skill popup form */

        $("#skill_id").select2({
            ajax: {
                url: '{{ url }}&a=find_skills',
                processResults: function (data) {
                    return {
                        results: data.items
                    };
                }
            },
            cache: false,
            placeholder: '{{ 'EnterTheSkillNameToSearch'|get_lang }}'
        }).on('change', function () {
            check_skills_sidebar();
        });

        load_nodes(0, main_depth);

        $('#frm-course-info').on('', function () {
            $('#frm-course-info').find('.modal-body').html('');
        });
        $(".facebook-auto").css("width","100%");
        $(".facebook-auto ul").css("width","100%");
        $("ul.holder").css("width","100%");
    });

</script>
<div id="page-back" class="page-skill">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 skill-options">
                <p class="skill-home">
                    <a class="btn btn-large btn-block btn-primary" href="{{ _p.web }}user_portal.php">
                        <em class="fa fa-home"></em> {{ "ReturnToCourseList"|get_lang }}
                    </a>
                </p>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <figure class="text-center">
                            <img width="100px" src="{{ user_info.avatar }}" class="img-circle center-block">
                            <figcaption class="avatar-author">{{ user_info.complete_name }}</figcaption>
                        </figure>
                        <p class="text-center">
                            <a href="{{ _p.web_main }}social/skills_ranking.php" class="btn btn-default" target="_blank">
                                {{ 'YourSkillRankingX'|get_lang|format(ranking) }}
                            </a>
                        </p>
                        <div class="text-center">
                            {% if skills is not empty %}
                                {% for skill in skills %}
                                    {{ skill.img_small }}
                                {% endfor %}
                            {% endif %}

                            {% for i in 1..(5 - ranking) %}
                                <img src="{{ 'badges-default.png'|icon(64) }}" width="64" height="64">
                            {% endfor %}
                        </div>
                    </div>
                </div>

                <!-- ACCORDION -->
                <div class="accordion" id="accordion2">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <a data-toggle="collapse" data-parent="#accordion2" href="#collapseTwo">
                                {{ 'GetNewSkills'|get_lang }}
                            </a>
                        </div>
                        <div id="collapseTwo" class="panel-collapse collapse">
                            <div class="panel-body">
                                <!-- SEARCH -->
                                <div class="search-skill">
                                    <h5 class="page-header">{{ 'SkillsSearch'|get_lang }}</h5>
                                    <form id="skill_search" class="form-search">
                                        <select id="skill_id" name="skill_id" multiple style="width: 100%;"></select>
                                        <table id="skill_holder" class="table table-condensed"></table>
                                    </form>
                                </div>
                                <!-- END SEARCH -->
                                <!-- INFO SKILL -->
                                <h5 class="page-header">{{ 'SkillInfo'|get_lang }}</h5>
                                <div id="skill_info"></div>
                                <!-- END INFO SKILL -->
                                <p>
                                    <a class="btn btn-default btn-block load_root" rel="0" href="#">
                                        <em class="fa fa-eye"></em> {{ "ViewSkillsWheel"|get_lang }}
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel-group" id="wheel-second-accordion" role="tablist" aria-multiselectable="true">
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="wheel-legend-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#wheel-second-accordion" href="#wheel-legend-collapse" aria-expanded="true" aria-controls="wheel-legend-collapse">
                                    {{ "Legend"|get_lang }}
                                </a>
                            </h4>
                        </div>
                        <div id="wheel-legend-collapse" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="wheel-legend-heading">
                            <div class="panel-body">
                                <ul class="fa-ul">
                                    <li>
                                        <em class="fa fa-li fa-square skill-legend-basic"></em> {{ "BasicSkills"|get_lang }}
                                    </li>
                                    <li>
                                        <em class="fa fa-li fa-square skill-legend-badges"></em> {{ "SkillsYouAcquired"|get_lang }}
                                    </li>
                                    <li>
                                        <em class="fa fa-li fa-square skill-legend-add"></em> {{ "SkillsYouCanLearn"|get_lang }}
                                    </li>
                                    <li>
                                        <em class="fa fa-li fa-square skill-legend-search"></em> {{ "SkillsSearchedFor"|get_lang }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="wheel-display-heading">
                            <h4 class="panel-title">
                                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#wheel-second-accordion" href="#wheel-display-collapse" aria-expanded="false" aria-controls="wheel-display-collapse">
                                    {{ 'DisplayOptions'|get_lang }}
                                </a>
                            </h4>
                        </div>
                        <div id="wheel-display-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="wheel-display-heading">
                            <div class="panel-body">
                                <p>{{ 'ChooseABackgroundColor'|get_lang }}</p>
                                <ul class="list-unstyled" id="skill-change-background-options">
                                    <li><a href="#" data-color="#FFFFFF">{{ 'White'|get_lang }}</a></li>
                                    <li><a href="#" data-color="#000000">{{ 'Black'|get_lang }}</a></li>
                                    <li><a href="#" data-color="#A9E2F3">{{ 'LightBlue'|get_lang }}</a></li>
                                    <li><a href="#" data-color="#848484">{{ 'Gray'|get_lang }}</a></li>
                                    <li><a href="#" data-color="#F7F8E0">{{ 'Corn'|get_lang }}</a></li>
                                </ul>
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
                <button type="button" class="btn btn-primary" data-dismiss="modal">
                    {{ "Close" | get_lang }}
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="frm-course-info" tabindex="-1" role="dialog" aria-labelledby="form-course-info-title" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ "Close" | get_lang }}">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="form-course-info-title">{{ "ChooseCourse" | get_lang }}</h4>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">{{ "Close" | get_lang }}</button>
            </div>
        </div>
    </div>
</div>
