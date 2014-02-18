<!DOCTYPE html>
<!--[if lt IE 7]> <html lang="{{ document_language }}" class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>    <html lang="{{ document_language }}" class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>    <html lang="{{ document_language }}" class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--><html lang="{{ document_language }}" class="no-js"> <!--<![endif]-->
<head>
{% include app.template_style ~ "/layout/head.tpl" %}
</head>
<body dir="{{ text_direction }}" class="{{ section_name }}">
<noscript>{{ "NoJavascript"|get_lang }}</noscript>

{% set classMain = '' %}
{% if show_toolbar == 1 %}
    {% set classMain = 'with-toolbar' %}
    {% set classMain = '' %}
{% endif %}

{% set containerClass = 'container' %}

{% if app.template.show_header == true %}
    <div class="skip">
        <ul>
            <li><a href="#menu">{{ "WCAGGoMenu"|get_lang }}</a></li>
            <li><a href="#content" accesskey="2">{{ "WCAGGoContent"|get_lang }}</a></li>
        </ul>
    </div>
    <header>
        <div class="{{ containerClass }}">
            <div class="row">
                <div id="header_left" class="col-md-4">
                    {# plugin_header left #}
                    {% if plugin_header_left is not null %}
                        <div id="plugin_header_left">
                            {{ plugin_header_left }}
                        </div>
                    {% endif %}
                </div>

                <div id="header_center" class="col-md-3">
                    {# plugin_header center #}
                    {% if plugin_header_center is not null %}
                        <div id="plugin_header_center">
                            {{ plugin_header_center }}
                        </div>
                    {% endif %}
                </div>

                <div id="header_right" class="col-md-5">
                    {#
                    <ul id="notifications" class="nav nav-pills pull-right">
                        {{ notification_menu }}
                    </ul>
                    #}
                    {# plugin_header right #}
                    {% if plugin_header_right is not null %}
                        <div id="plugin_header_right">
                            {{ plugin_header_right }}
                        </div>
                    {% endif %}
                </div>
            </div>
        </div>

        {% if plugin_header_main %}
            <div class="row">
                <div class="col-md-12">
                    <div id="plugin_header_main">
                        {{ plugin_header_main }}
                    </div>
                </div>
            </div>
        {% endif %}

        {# menu #}
        {% include app.template_style ~ "/layout/menu.tpl" %}
    </header>

    <div id="wrapper">
        {# Bug and help notifications #}

        <ul id="navigation" class="notification-panel">
            {% if ("enable_help_link" | get_setting) == 'true' %}
                <li class="help">
                    <a href="{{ _p.web_main }}help/help.php?open={{ help_content }}&height=400&width=600" class="ajax" title="{{ "help"|get_lang }}">
                        <img src="{{ _p.web_img }}help.large.png" alt="{{ "help"|get_lang }}" title="{{ "help"|get_lang }}" />
                    </a>
                </li>
            {% endif %}

            {% if ("show_link_bug_notification" | get_setting) == 'true' and _u.logged != 0 %}
            <li class="report">
                {% set bugLink = ("bug_report_link" | get_setting) %}
                {% if ("bug_report_link" | get_setting) is empty %}
                    {% set bugLink = 'http://support.chamilo.org/projects/chamilo-18/wiki/How_to_report_bugs' %}
                {% endif %}
                <a href="{{ bugLink }}" target="_blank">
                    <img src="{{ _p.web_img }}bug.large.png" alt="{{ "ReportABug"|get_lang }}" title="{{ "ReportABug"|get_lang }}"/>
                </a>
            </li>
            {% endif %}
        </ul>

        {# topbar #}
        {% include app.template_style ~ "/layout/topbar.tpl" %}

        {% block main_div_container %}
            <div id="main" class="{{ containerClass }} {{ classMain }}">
        {% endblock main_div_container %}
            <div id="top_main_content" class="row">
                {% if _u.logged == 1 %}
                <div class="col-lg-2 col-sm-1 sidebar">
                    <div id="sidebar">
                        <div class="shortcuts">
                            <div class="shortcuts-large">
                                <a class="btn btn-success" href="{{ _p.web_main }}calendar/agenda_js.php?type=personal">
                                    <i class="fa fa-calendar"></i>
                                </a>
                                <a class="btn btn-info" href="{{ _p.web_main }}mySpace/index.php">
                                    <i class="fa fa-signal"></i>
                                </a>
                                <a class="btn btn-warning" href="{{ _p.web_main }}social/home.php">
                                    <i class="fa fa-users"></i>
                                </a>
                                <a class="btn btn-danger" href="{{ settings_link }}">
                                    <i class="fa fa-cogs"></i>
                                </a>
                            </div>
                            <div class="shortcuts-mini" style="display:none">
                                c
                            </div>
                        </div>
                        <ul class="nav main-menu">
                            <li class="active">
                                <a href="#">
                                    <i class="fa fa-home fa-lg"></i>
                                    <span class="text">Dashboard</span>
                                </a>
                            </li>
                            <li><a href="#"><i class="fa fa-user fa-lg"></i> <span class="text">Users</a></span></li>
                            <li><a href="#"><i class="fa fa-book fa-lg"></i> <span class="text">Courses</a></span></li>
                        </ul>
                        <span class="minify">
                            <i class="fa fa-arrow-circle-left hit"></i>
                        </span>
                    </div>
                </div>
                {% endif %}
            {# course navigation links/shortcuts need to be activated by the admin #}
            {% include app.template_style ~ "/layout/course_navigation.tpl" %}
{% endif %}
