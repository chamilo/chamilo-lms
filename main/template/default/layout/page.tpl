<!DOCTYPE html>
<!--[if lt IE 7]> <html lang="{{ document_language }}" class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>    <html lang="{{ document_language }}" class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>    <html lang="{{ document_language }}" class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!-->
<html lang="{{ document_language }}" class="no-js"> <!--<![endif]-->
<head>
{% block head %}
{% include template ~ "/layout/head.tpl" %}
{% endblock %}
</head>
<body dir="{{ text_direction }}" class="{{ section_name }} {{ login_class }}">
<noscript>{{ "NoJavascript"|get_lang }}</noscript>
<div class="wrap">
    {% if displayCookieUsageWarning == true %}
    <!-- Display Cookies validation -->
    <div class="toolbar-cookie alert-warning">
        <form onSubmit="$(this).toggle('slow')" action="" method="post">
            <input value=1 type="hidden" name="acceptCookies"/>
            <div class="cookieUsageValidation">
                {{ "YouAcceptCookies" | get_lang }}
                <span style="margin-left:20px;" onclick="$(this).next().toggle('slow'); $(this).toggle('slow')">
                    ({{"More" | get_lang }})
                </span>
                <div style="display:none; margin:20px 0;">
                    {{ "HelpCookieUsageValidation" | get_lang}}
                </div>
                <span style="margin-left:20px;" onclick="$(this).parent().parent().submit()">
                    ({{"Accept" | get_lang }})
                </span>
            </div>
        </form>
    </div>
    {% endif %}

    {% if show_header == true %}
    {% include template ~ "/layout/page_header.tpl" %}
    {% endif %}
    {% if show_course_shortcut is not null %}
        <div class="nav-tools">
            {{ show_course_shortcut }}
        </div>
    {% endif %}
	<section id="content-section">
        <div class="container">
            {% block breadcrumb %}
                <div id="page-breadcrumb">
                    {{ breadcrumb }}
                </div>
            {% endblock %}

            {% block body %}
                {{ content }}
            {% endblock %}
        </div>
	</section>

    {% if show_footer == true %}
	{% include template ~ "/layout/page_footer.tpl" %}
    {% endif %}
    </div>
  </body>
</html>