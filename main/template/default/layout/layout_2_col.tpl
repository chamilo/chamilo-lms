{% extends 'layout/page.tpl'|get_template %}
{% import 'default/macro/macro.tpl' as display %}
{% set sidebar_hide = 'sidebar_hide'|api_get_configuration_value %}

{% block body %}
    {% if plugin_main_top %}
        {{ display.pluginPanel('main-top', plugin_main_top) }}
    {% endif %}
    <div class="row">
        <div class="col-md-9 col-md-push-3">
            <div class="page-content">
                {% if plugin_content_top %}
                    <div class="page-content-top">
                        {{ plugin_content_top }}
                    </div>
                {% endif %}

                {{ sniff_notification }}

                {% block page_body %}
                    {% include 'layout/page_body.tpl'|get_template %}
                {% endblock %}

                {% if home_welcome %}
                    <article id="home-welcome">
                        {{ home_welcome }}
                    </article>
                {% endif %}

                {% if home_include %}
                <article id="home-include">
                    {{ home_include }}
                </article>
                {% endif %}


                {% if welcome_to_course_block %}
                    <article id="homepage-course">
                        {{ welcome_to_course_block }}
                    </article>
                {% endif %}

                {% block content %}
                    {% if content is not null %}
                        <section id="page" class="{{ course_history_page }}">
                            {{ content }}
                        </section>
                    {% endif %}
                {% endblock %}

                {% if announcements_block %}
                    <article id="homepage-announcements">
                        {{ announcements_block }}
                    </article>
                {% endif %}

                {% if course_category_block %}
                    <article id="homepage-course-category">
                        {{ course_category_block }}
                    </article>
                {% endif %}
                {% include 'layout/hot_courses.tpl'|get_template %}
                {% include 'session/sessions_current.tpl'|get_template %}
                {% if plugin_content_bottom %}
                    <div id="plugin_content_bottom">
                        {{ plugin_content_bottom }}
                    </div>
                {% endif %}
            </div>
        </div>
        <div class="col-md-3 col-md-pull-9">
            <div class="sidebar">
                {% if plugin_menu_top %}
                    {{ display.pluginSidebar('sidebar-top', plugin_menu_top) }}
                {% endif %}

                {% include 'layout/login_form.tpl'|get_template %}

                {% if not sidebar_hide %}

                    {% if _u.logged  == 1 %}
                        {{ user_image_block }}
                    {% endif %}

                    <!-- BLOCK PROFILE -->
                    {% if profile_block %}
                        {{ display.collapseMenu('profile', 'Profile'|get_lang, profile_block) }}
                    {% endif %}

                    <!-- BLOCK COURSE -->
                    {% if course_block %}
                        {{ display.collapseMenu('courses', 'Courses'|get_lang, course_block) }}
                    {% endif %}

                    <!-- BLOCK SKILLS -->
                    {% if skills_block %}
                        {{ display.collapseMenu('skills', 'Skills'|get_lang, skills_block) }}
                    {% endif %}

                    <!-- BLOCK WORK -->
                    {% if student_publication_block %}
                        {{ display.collapseMenu('student_publications', 'StudentPublications'|get_lang, student_publication_block) }}
                    {% endif %}

                    {% if grade_book_sidebar %}
                        <div class="panel-group" id="skill" role="tablist" aria-multiselectable="true">
                        <div class="panel panel-default" id="gradebook_block">
                            <div class="panel-heading" role="tab">
                                <h4 class="panel-title">
                                    <a role="button" data-toggle="collapse"
                                       data-parent="#skill"
                                       href="#skillCollapse"
                                       aria-expanded="true"
                                       aria-controls="skillCollapse">
                                        {{ 'Gradebook' | get_lang }}
                                    </a>
                                </h4>
                            </div>
                            <div style="" aria-expanded="true" id="skillCollapse" class="panel-collapse collapse in" role="tabpanel">
                                <div class="panel-body">
                                    <ul class="list-group">
                                        <li class="list-group-item {{ item.class }}">
                                            {{ 'Progress' | get_lang  }} : {{ grade_book_progress }} %
                                            <br />
                                            {% for badge in grade_book_badge_list %}
                                                <div class="badge_sidebar">
                                                {% for skill in badge.skills %}
                                                    {% if badge.finished %}
                                                        <img class="badge_sidebar_image " src ="{{ skill.icon_big }}" />
                                                    {% else %}
                                                        <img class="badge_sidebar_image badge_sidebar_image_transparency"
                                                             src = "{{ skill.icon_big }}" />
                                                    {% endif %}
                                                    <div class="badge_sidebar_title">
                                                    {{ skill.name }}
                                                    </div>
                                                {% endfor %}
                                                </div>
                                            {% endfor %}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    {% endif %}
                    {{ certificates_search_block }}
                    <!-- BLOCK NOTICE -->
                    {% if notice_block %}
                        {{ display.collapse('notice', 'Notice'|get_lang, notice_block) }}
                    {% endif %}
                    <!-- BLOCK HELP -->
                    {% if help_block %}
                        {{ display.collapse('help', 'MenuGeneral'|get_lang, help_block, true) }}
                    {% endif %}
                    <!-- BLOCK LINKS NAVIGATION -->
                    {% if navigation_links %}
                        {{ display.collapseFor('navigation_sidebar', 'MainNavigation'|get_lang, navigation_links) }}
                    {% endif %}
                    {{ search_block }}
                    {{ classes_block }}

                    {% if plugin_menu_bottom %}
                        {{ display.pluginSidebar('sidebar-bottom', plugin_menu_bottom) }}
                    {% endif %}
                {% endif %}
            </div>
        </div>
    </div>
    {% if plugin_main_bottom %}
        {{ display.pluginPanel('main-bottom', plugin_main_bottom) }}
    {% endif %}
{% endblock %}
