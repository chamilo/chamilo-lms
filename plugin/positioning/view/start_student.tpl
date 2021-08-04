<div class="container">
    <div class="row">
        <div class="col-md-5">
            <img style="float:left;" src="{{ _p.web }}plugin/positioning/img/positioning-line.png" />
            <table style="height:380px;margin-left: auto;margin-right: auto;" >
                <tr>
                    <td>{{ "InviteToTakePositioningTest"| get_plugin_lang('Positioning') }}<br />
                        {{ "InitialTest"| get_plugin_lang('Positioning') }}: {{ initial_exercise }}
                    </td>
                </tr>
                <tr>
                    <td>
                        {{ "YouMustCompleteAThresholdToTakeFinalTest"| get_plugin_lang('Positioning') | format(average_percentage_to_unlock_final_exercise) }}
                        <br />{{ "Average"| get_lang }}: {{ lp_url_and_progress }}
                    </td>
                </tr>
                <tr>
                    <td>
                        {{ "FinalTest"| get_plugin_lang('Positioning') }}: {{ final_exercise }}
                    </td>
                </tr>
            </table>
        </div>
        <div class="col-md-7">
            <br />
            <br />
            {{ radars }}
        </div>
    </div>
</div>


