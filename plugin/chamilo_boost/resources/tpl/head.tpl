<meta charset="{{ system_charset }}" />
<link href="https://chamilo.org/chamilo-lms/" rel="help" />
<link href="https://chamilo.org/the-association/" rel="author" />
<link href="https://www.gnu.org/licenses/gpl-3.0.en.html" rel="license" />
<!-- Force latest IE rendering engine or ChromeFrame if installed -->
<!--[if IE]>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<![endif]-->
{{ prefetch }}
{{ favico }}
<link rel="apple-touch-icon" href="{{ _p.web }}apple-touch-icon.png" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="generator" content="{{ _s.software_name }} {{ _s.system_version|slice(0,1) }}" />
{#  Use the latest engine in ie8/ie9 or use google chrome engine if available  #}
{#  Improve usability in portal devices #}
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ title_string }}</title>

{% set havespeedload = 0 %}

{% if _p.self_basename == "index.php" %}
    {% set havespeedload = 1 %}
{% endif %}
{% if "mySpace" in _p.web_self %}
    {% set havespeedload = 2 %}
{% endif %}
{% if "courses" in _p.web_self %}
    {% set havespeedload = 2 %}
{% endif %}

{% if _p.self_basename == "course_home.php" 
or _p.self_basename == "home.php" 
or _p.self_basename == "user_list.php"
or _p.self_basename == "exercise.php"
or _p.self_basename == "user_portal.php"
 %}
    {% set havespeedload = 1   %}
{% endif %}

{% if havespeedload == 0 or havespeedload == 2 %}

    {{ social_meta }}
    {{ css_static_file_to_string }}
    {{ js_file_to_string }}

    {{ extra_headers }}

    {% if _s.language_interface %}
    <script type="text/javascript" src="{{ _p.web }}web/build/main.{{ _s.language_interface }}.js"></script>
    {% else %}{# language_interface *should* always be defined, so we should never come here #}
    <script type="text/javascript" src="{{ _p.web }}web/build/main.js"></script>
    {% endif %}

    {{ css_custom_file_to_string }}
    {{ css_style_print }}

    {% if havespeedload == 2 %}
        <script type="text/javascript">
            if(_p["web_self"].indexOf("mySpace")!=-1){
                setTimeout(function(){
                    if (typeof recupLoginName === "function") { 
                        recupLoginName();
                        initBoostPage();
                    }
                },500);
            }
        </script>
    {% endif %}

{% else %}

    <script type="text/javascript" src="{{ _p.web }}web/assets/jquery/dist/jquery.min.js"></script>

    {{ extra_headers }}

{% endif %}

   
