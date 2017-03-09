<!DOCTYPE html>
<!--[if lt IE 7]> <html lang="{{ document_language }}" class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>    <html lang="{{ document_language }}" class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>    <html lang="{{ document_language }}" class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--><html lang="{{ document_language }}" class="no-js"> <!--<![endif]-->
<head>
{% block head %}
{% include template ~ "/layout/head.tpl" %}
{% endblock %}
</head>
<body dir="{{ text_direction }}" class="{{ section_name }} {{ login_class }}">
<noscript>{{ "NoJavascript"|get_lang }}</noscript>

<!-- Display the Chamilo Uses Cookies Warning Validation if needed -->
{% if displayCookieUsageWarning == true %}
    <!-- If toolbar is displayed, we have to display this block bellow it -->
    {% if toolBarDisplayed == true %}
        <div class="displayUnderToolbar" >&nbsp;</div>
    {% endif %}
    <form onSubmit="$(this).toggle('slow')" action="" method="post">
        <input value=1 type="hidden" name="acceptCookies"/>
        <div class="cookieUsageValidation">
            {{ "YouAcceptCookies" | get_lang }}
            
            <span class="link" onclick="$(this).next().toggle('slow'); $(this).toggle('slow')">
                {{"ReadMore" | get_lang }}
            </span>
            <div class="more" style="display:none;">
                {{ "HelpCookieUsageValidation" | get_lang}}
            </div>
            <span class="link" onclick="$(this).parent().parent().submit()">
                {{"Accept" | get_lang }}
            </span>
        </div>
    </form>
{% endif %}


{% if show_header == true %}

<div id="page-wrap"><!-- page section -->
    <div id="navigation" class="notification-panel">
        {{ help_content }}
        {{ bug_notification }}
    </div>
    {% block topbar %}
        {% include template ~ "/layout/topbar.tpl" %}
        {% if show_toolbar == 1 %}
            <div class="clear-header"></div>
        {% endif %}
    {% endblock %}
    
        <header>
            <div class="extra-header">{{ header_extra_content }}</div>
            <section id="main" class="container header-logo-top">
                {% if plugin_header_main %}
                <div class="row">
                    <div class="col-lg-12">
                        {{ plugin_header_main }}
                    </div>
                </div>
                {% endif %}
                <div class="row">
                    <div class="col-xs-6 col-md-4">
                        <div class="logo pull-left">
                            <a href="{{ _p.web }}"><img class="img-responsive" src="{{ _p.web_css_theme }}images/header-logo.png"/></a>
                        </div>
                        <!-- <h2><a href="{{ _p.web }}">{{ _s.site_name }}</a></h2> -->
                    </div>
                    <div class="col-xs-6 col-md-4">
                         
                    </div>
                        <div class="col-xs-6 col-md-4">
                            <div class="logo-ofaj pull-right">
                                <a href="#"><img class="img-responsive" src="{{ _p.web_css_theme }}images/logo-ofaj.png"/></a> 
                            </div>
                        </div>
                </div>
            </section>
            <section id="menu-bar">
                {% block menu %}
                {% include template ~ "/layout/menu.tpl" %}
                {% endblock %}
            </section>
            <section id="breadcrumb-bar">
                <div class="container">
                    {% block breadcrumb %}
                        {{ breadcrumb }}
                    {% endblock %}
                </div>
            </section>
        </header>
    <div id="top_main_content">
       
{% endif %}
