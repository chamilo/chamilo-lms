{# start copy from head.tpl #}
<meta charset="{{ system_charset }}" />
<link href="https://chamilo.org/chamilo-lms/" rel="help" />
<link href="https://chamilo.org/the-association/" rel="author" />
<link href="https://chamilo.org/the-association/" rel="copyright" />
{{ prefetch }}
{{ favico }}
{{ browser_specific_head }}
<link rel="apple-touch-icon" href="{{ _p.web }}apple-touch-icon.png" />
<link href="{{ _p.web_plugin }}advanced_subscription/views/css/style.css" rel="stylesheet" type="text/css">
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="Generator" content="{{ _s.software_name }} {{ _s.system_version|slice(0,1) }}" />
{#  Use the latest engine in ie8/ie9 or use google chrome engine if available  #}
{#  Improve usability in portal devices #}
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ title_string }}</title>
{{ css_file_to_string }}
{{ css_style_print }}
{{ js_file_to_string }}
{# end copy from head.tpl #}
<div class="legal-terms-popup">
    {{ termsContent }}
</div>