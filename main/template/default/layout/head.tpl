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
{{ social_meta }}
{{ css_static_file_to_string }}
{{ js_file_to_string }}
{{ extra_headers }}

{% if _s.language_interface %}
<script src="{{ _p.web }}web/build/main.{{ _s.language_interface }}.js"></script>
{% else %}{# language_interface *should* always be defined, so we should never come here #}
<script src="{{ _p.web }}web/build/main.js"></script>
{% endif %}

{{ css_custom_file_to_string }}
{{ css_style_print }}
