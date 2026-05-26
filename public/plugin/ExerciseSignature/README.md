# ExerciseSignature

Adds an optional student signature step to completed exercise attempts.

## Chamilo 2 behavior

- Teachers enable the signature per exercise with the `signature_activated` extra field.
- The student can sign only their own completed attempt.
- The signature is stored as a data URL in the `track_exercise.signature` extra field.
- If the plugin is disabled, the exercise result page returns to the normal Chamilo behavior.

## Notes

This plugin does not modify quiz scores, answers, questions, or gradebook data.

## v2 notes

Installation now sets `filter = 0` for extra fields because Chamilo 2 can define `extra_field.filter` as `NOT NULL`. The result UI uses Tailwind utilities and Chamilo theme colors.
