<div class="row">
    <div class="col-md-4">
        <h3>{{ "InviteToTakePositioningTest"| get_plugin_lang('Positioning') }}</h3>
        <p>{{ "InitialTest"| get_plugin_lang('Positioning') }}: {{ initial_exercise }}</p>
    </div>

    <div class="col-md-4">
        <h3>{{ "YouMustCompleteAThresholdToTakeFinalTest"| get_plugin_lang('Positioning') | format(average_percentage_to_unlock_final_exercise) }}</h3>
        <p>{{ "Average"| get_lang }}: {{ average }} %</p>
        <p>{{ lp_url }}</p>
    </div>

    <div class="col-md-4">
        <p>{{ "FinalTest"| get_plugin_lang('Positioning') }}: {{ final_exercise }}</p>
    </div>
</div>

{{ radars }}
