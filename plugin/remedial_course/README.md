Create a field to remedial course
======

The purpose of this plugin is to offer the possibility of adding remedial courses or advanced courses so that, when a test is 
validated or failed, the learner will be automatically subscribed to the corresponding courses. 
If the test is realised in the context of a session then the remedial and advanced courses will be subscribe in the same session.

* For remedial courses:
  When activating the plugin, in the test settings, a remedialCourseList field is enabled, for the teacher to define the courses
  where the learner will be subscribed in case of failure of the test at all the attempt until the last one.
  For this to work, the number of attempts must be activated and also, have an exam success rate enabled.
  After the user fails the last attempt, they automatically enroll in the selected courses.

* For advanced courses:
  When activating the plugin, in the test settings, the advanceCourseList field is enabled, which allows you to select 
  one or more courses to which the learners will be subscribed when they validate/succeed an exam (that means their 
  result is higher than the passing percentage.
  This function is independent of the number of attempts, but the passing percentage must be established from the test 
  configuration.
