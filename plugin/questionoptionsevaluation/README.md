# Questions Options Evaluation

Allow recalulate the options score in questions:

* Successes - Failures
* Successes - Failures / 2
* Successes - Failures / 3
* Recalculate question scores

**Setup instructions**

- Install plugin
- Set enabled in configuration
- Edit `configuration.php` file
  ```php
  $_configuration['exercise_additional_teacher_modify_actions'] = [
      // ...
      'questionoptionsevaluation' => ['QuestionOptionsEvaluationPlugin', 'filterModify']
  ];
  ```
