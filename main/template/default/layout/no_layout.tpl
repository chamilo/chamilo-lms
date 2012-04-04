<!DOCTYPE html>
<!--[if lt IE 7]> <html lang="{{document_language}}" class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>    <html lang="{{document_language}}" class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>    <html lang="{{document_language}}" class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--><html lang="{{document_language}}" class="no-js"> <!--<![endif]-->
<head>
{% include "default/layout/head.tpl" %}
</head>
<body dir="{{text_direction}}" class="{{section_name}}">
{% block name%}
{% endblock %}
{% block body %}
    {{content}}
{% endblock %}
</body>
</html>