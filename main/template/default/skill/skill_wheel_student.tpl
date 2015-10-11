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
        return '<li>' + 
            '<a id="skill_to_select_id_' + skill_id + '" href="#" class="load_wheel" rel="' + skill_id + '">' +
                skill_name +
            '</a>' +
            '</li>';
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
        $("#skill_holder").on("click", "a.load_wheel", function () {
            skill_id = $(this).attr('rel');
            skill_to_load_from_get = 0;
            load_nodes(skill_id, main_depth);
            load_skill_info(skill_id);
        });

        /* URL link when searching skills */
        $("#skill_search").on("click", "a.load_root", function () {
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

        /* Wheel skill popup form */

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

        $('#frm-course-info').on('', function () {
            $('#frm-course-info').find('.modal-body').html('');
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
                <div class="skill-profile">
                    <div class="avatar">
                        <img width="100px" src="{{ user_info.avatar }}" style="text-align: center">
                    </div>
                    <div class="info-user">
                        <h4 class="title-skill">{{ user_info.complete_name }}</h4>
                        <p>
                            <a href="{{ _p.web_main }}social/skills_ranking.php" class="btn btn-default btn-block" target="_blank">{{ 'YourSkillRankingX' | get_lang | format(ranking) }}</a>
                        </p>
                        {% if skills is not empty %}
                            {% for skill in skills %}
                                {% if skill.icon is empty %}
                                    <img src="{{ 'badges.png' | icon(32) }}" width="32" height="32" alt="{{ skill.name }}" title="{{ skill.name }}">
                                {% else %}
                                    <img src="{{ skill.web_icon_thumb_path }}" width="32" height="32" alt="{{ skill.name }}" title="{{ skill.name }}">
                                {% endif %}
                            {% endfor %}
                        {% endif %}

                        {% for i in 1..(9 - ranking) %}
                            <img src="{{ 'badges-default.png' | icon(32) }}" width="32" height="32">
                        {% endfor %}
                    </div>
                </div>

                <!-- ACCORDION -->
                <div class="accordion" id="accordion2">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <a data-toggle="collapse" data-parent="#accordion2" href="#collapseTwo">
                                {{ 'GetNewSkills' | get_lang }}
                            </a>
                        </div>
                        <div id="collapseTwo" class="panel-collapse collapse">
                            <div class="panel-body">
                                <!-- SEARCH -->
                                <div class="search-skill">
                                    <p class="text">{{ 'EnterTheSkillNameToSearch' | get_lang }}</p>
                                    <form id="skill_search" class="form-search">
                                        <select id="skill_id" name="skill_id" /></select>
                                        <div class="button-skill">
                                            <a class="btn btn-default btn-block load_root" rel="0" href="#">
                                                <i class="fa fa-eye"></i> {{ "ViewSkillsWheel"|get_lang }}
                                            </a>
                                            <!-- <a id="clear_selection" class="btn btn-danger">{{ "Clear"|get_lang }}</a> -->
                                        </div>
                                        <ul id="skill_holder" class="holder_simple"></ul>
                                    </form>
                                </div>
                                <!-- END SEARCH -->
                                <!-- INFO SKILL -->
                                <div class="section-info-skill">
                                    <p class="text">{{ 'SkillInfo'|get_lang }}</p>
                                    <div id="skill_info"></div>
                                </div>
                                <!-- END INFO SKILL -->
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
                            <a   data-toggle="collapse" data-parent="#accordion2" href="#collapseFour">
                                {{ "Legend"|get_lang }}
                            </a>
                        </div>
                        <div id="collapseFour" class="panel-collapse collapse">
                            <div class="panel-body">
                                <p class="text"><span class="skill-legend-badges">&nbsp;&nbsp;&nbsp;&nbsp;</span> {{ "SkillsYouAcquired"|get_lang }}</p>
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
