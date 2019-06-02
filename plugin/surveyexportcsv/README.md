# Survey Export CSV

Exports survey results to a CSV file with a very specific format.

This plugin will add a new action button in the surveys list, allowing the 
teacher to export the survey in a CSV format meant at exchanging with external
analysis tools.

The CSV format looks this way:

```
DATID;P01;P02;P03;P04;P05;P06;P07;P08;DATOBS
1;"1";"2";"26";"10";"2";"2";"2";"4";"2"
2;"1";"2";"32";"10";"6";"4";"4";"5";"2"
3;"2";"3";"27";"8";"5";"5";"2";"5";"1"
4;"1";"3";"33";"11";"1";"4";"1";"6";"1"
```

Where:
- DATID represents a sequential ID for the participants (not related to
their internal user ID)
- P01,P02,... represent the sequential ID of each question inside the survey
- DATOBS represents the free answer of the user to an open remarks form at 
the end of the survey

**Setup instructions**

- Install plugin
- Set enabled in configuration
- Edit `configuration.php` file
  ```php
  $_configuration['survey_additional_teacher_modify_actions'] = [
      // ...
      'SurveyExportCSVPlugin' => ['SurveyExportCsvPlugin', 'filterModify'],
  ];
  ```
If you have large surveys with large numbers of users answering them, you
might want to ensure your c_survey_answer table is properly indexed. If not,
use the following SQL statement to modify that:
```sql
alter table c_survey_answer add index idx_c_survey_answerucsq (user, c_id, survey_id, question_id);
```