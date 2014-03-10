{% include app.template_style ~ "/layout/head.tpl" %}
{% set classBody = 'no-sidebar' %}
{% if app.template.show_header == true %}
    {% set classBody = '' %}
{% endif %}
<body dir="{{ text_direction }}" class="{{ section_name }} {{ classBody }}">
{% set classMain = '' %}
{% if show_toolbar == 1 %}
    {% set classMain = 'with-toolbar' %}
    {% set classMain = '' %}
{% endif %}

{% set containerClass = 'container-fluid' %}

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

    {# Bug and help notifications #}
    {% if 0 %}
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
    {% endif %}

    {# topbar #}
    {% include app.template_style ~ "/layout/topbar.tpl" %}

    <div id="main" class="{{ containerClass }}">
        <div class="row">
            {% include app.template_style ~ "/layout/sidebar.tpl" %}

    {# course navigation links/shortcuts need to be activated by the admin #}
    {% include app.template_style ~ "/layout/course_navigation.tpl" %}
{% endif %}
