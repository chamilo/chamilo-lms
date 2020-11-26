<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="row">
                <div class="col-md-6">
                    <img style="float:left;" src="/plugin/positioning/img/positioning-line.png" >

                    <table style="height:380px;margin-left: auto;margin-right: auto;" >
                        <tr>
                            <td>{{ "InviteToTakePositioningTest"| get_plugin_lang('Positioning') }}{{ "InitialTest"| get_plugin_lang('Positioning') }}:<br>{{ initial_exercise }}</td>
                        </tr>
                        <tr>
                            <td>{{ "YouMustCompleteAThresholdToTakeFinalTest"| get_plugin_lang('Positioning') | format(average_percentage_to_unlock_final_exercise) }}<br>{{ "Average"| get_lang }}: {{ average }} %</td>
                        </tr>
                        <tr>
                            <td>{{ "FinalTest"| get_plugin_lang('Positioning') }}: {{ final_exercise }}</td>
                        </tr>
                    </table>
                </div>
                <div>
                    {{ radars }}
                </div>
            </div>
        </div>
    </div>
</div>


