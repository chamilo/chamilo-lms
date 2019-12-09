<!DOCTYPE html>
<head>
    {{ css_certificate }}
</head>
<body style="margin: 0px; padding: 0px;">
    {% if background %}
        <div style="background-image: url('{{ background }}'); background-size: cover; width: 1200px; height: 793px; position: relative;">
    {% else %}
        <div style="width: 1200px; height: 793px; position: relative;">
    {% endif %}

    {{ content_html }}

    {% if seal %}
    <div style="position: absolute; bottom: 80px; right: 100px;">
        <img src="{{ seal }}"  width="180px;"/>
    </div>
    {% endif %}
</div>
</body>
</html>