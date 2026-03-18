{% if tool.description %}
<p class="lead">{{ tool.description|e|nl2br }}</p>
{% endif %}

<div class="embed-responsive embed-responsive-4by3">
    <iframe
            src="{{ launch_url }}"
            class="plugin-ims-lti-iframe"
            style="width: 100%; min-height: 700px; border: 0;"
    ></iframe>
</div>
