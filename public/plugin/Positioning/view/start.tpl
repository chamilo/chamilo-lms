{{ positioning_introduction|raw }}

{{ table|raw }}

{% if radars %}
{{ radars|raw }}
<br>
{{ "ChartShowsAverageForAllStudentsUsingZeroForIncompleteTests"|get_plugin_lang('Positioning') }}
{% endif %}
