# Survey Export CSV

Export surveys to CSV file.

This plugin will add a new action button in survey list allowing export the survey.

**Instructions**

- Install plugin
- Set enabled in configuration
- Edit `configuration.php` file
  ```php
  $_configuration['survey_additional_teacher_modify_actions'] = [
      // ...
      'SurveyExportCSVPlugin' => ['SurveyExportCsvPlugin', 'filterModify'],
  ];
  ```
