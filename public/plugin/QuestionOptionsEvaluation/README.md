# Question options evaluation

This plugin lets a teacher apply option-based negative marking rules to a test.

## Formulas

- **No formula**: keep the default Chamilo scoring behavior for the test.
- **Recalculate question scores**: recalculate the option ponderations in the selected test, without applying a custom final score formula.
- **Successes - Failures**: final score is based on correct answers minus incorrect answers.
- **Successes - Failures / 2**: final score is based on correct answers minus half of incorrect answers.
- **Successes - Failures / 3**: final score is based on correct answers minus one third of incorrect answers.

## Setup

1. Install the plugin from Administration > Plugins.
2. Activate it from the plugin list.
3. Configure the maximum score if needed. Default is 10.
4. Open the formula configuration from the test action or directly from the plugin URL.

## Notes

Changing a formula can modify question and option scores in the selected test. This operation should be tested on a copy of a test before applying it in production.


## Teacher action integration

When the plugin is installed or configured, it registers `QuestionOptionsEvaluationPlugin::filterModify`
in the `exercise.exercise_additional_teacher_modify_actions` platform setting.

The action is shown only when the plugin is active. If the plugin is disabled, the registered callback
returns an empty string and the test list keeps working without showing the button.

The plugin uses `WEB_PLUGIN_PATH` to build its own URL, so it does not depend on legacy course web paths.


## Course context note

The teacher action URL must preserve the current Chamilo course context with `api_get_cidreq()`.
Without `cid`, `sid`, and `gid`, `evaluation.php` is loaded outside the course environment and
`api_protect_course_script()` will reject the request.


## Chamilo 2 compatibility notes

The teacher action URL must preserve the current course context (`cid`, `sid`, and `gid`) because `evaluation.php` is protected by Chamilo course access checks.

The formula storage uses the Chamilo 2 exercise identifier returned by `Exercise::getId()`. Do not use the legacy/non-existing `Exercise::$iid` property.


## Safe scoring behavior

The plugin stores the selected formula in an exercise extra field and does not modify the original question or answer ponderations when saving the configuration.

This is intentional: disabling the plugin must allow Chamilo to return to its original scoring behavior.
