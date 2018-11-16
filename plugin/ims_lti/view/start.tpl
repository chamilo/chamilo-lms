{%  if tool.description %}
    <p class="lead">{{ tool.description|e|nl2br }}</p>
{%  endif %}
<div class="embed-responsive embed-responsive-4by3">
    <iframe src="{{ launch_url }}" class="plugin-ims-lti-iframe"></iframe>
</div>
