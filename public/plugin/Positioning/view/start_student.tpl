<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
        <div class="flex flex-col items-center">
            <img src="/plugin/Positioning/img/positioning-line.png" alt="Positioning line" class="w-40 h-auto mb-6" />

            <div class="space-y-6 text-center text-gray-700">
                <div class="bg-white shadow rounded-xl p-4">
                    <p class="font-semibold text-lg">
                        {{ "InviteToTakePositioningTest"| get_plugin_lang('Positioning') }}
                    </p>
                    <p class="text-blue-600 mt-1">
                        {{ "InitialTest"| get_plugin_lang('Positioning') }}: {{ initial_exercise|raw }}
                    </p>
                </div>

                <div class="bg-white shadow rounded-xl p-4">
                    <p class="font-semibold text-lg">
                        {{ "YouMustCompleteAThresholdToTakeFinalTest"| get_plugin_lang('Positioning') | format(average_percentage_to_unlock_final_exercise) }}
                    </p>
                    <p class="text-green-600 mt-1">
                        {{ "Average"| get_lang }}: {{ lp_url_and_progress|raw }}
                    </p>
                </div>

                <div class="bg-white shadow rounded-xl p-4">
                    <p class="font-semibold text-lg">
                        {{ "FinalTest"| get_plugin_lang('Positioning') }}: {{ final_exercise|raw }}
                    </p>
                </div>
            </div>
        </div>

        <div>
            {{ radars|raw }}
        </div>
    </div>
</div>
