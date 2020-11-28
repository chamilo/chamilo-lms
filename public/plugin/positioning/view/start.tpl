
{{ positioning_introduction }}

{{ table }}

{% if radars %}
    {{ radars }}
    <br>
    {{ "ChartShowsAverageForAllStudentsUsingZeroForIncompleteTests"| get_plugin_lang('Positioning') }}
{% endif %}
