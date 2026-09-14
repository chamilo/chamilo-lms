<div dir="auto">{{ positioning_introduction|raw }}</div>

{{ table|raw }}

{% if radars %}
{{ radars|raw }}
<br>
{{ "ChartShowsAverageForAllStudentsUsingZeroForIncompleteTests"|get_plugin_lang('Positioning') }}
{% endif %}
