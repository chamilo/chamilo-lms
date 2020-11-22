<h3>{{ "InviteToTakePositioningTest"| get_plugin_lang('Positioning') }}</h3>

<p>{{ "InitialTest"| get_plugin_lang('Positioning') }}: {{ initial_exercise }}</p>

<h3>{{ "YouMustCompleteAThresholdToTakeFinalTest"| get_plugin_lang('Positioning') | format(average_percentage_to_unlock_final_exercise) }}</h3>

<p>{{ "Average"| get_lang }}: {{ average }}</p>

<p>{{ "FinalTest"| get_plugin_lang('Positioning') }}: {{ final_exercise }}</p>

{{ radars }}
