# External Notification Connect

Activate external notification system, that will send notificatin to an external REST webservice on specific Chamilo's action.
For the moment it will send notification on :
* LearningPath creation (Chamilo LP or Scorm import)
* Portfolio post creation, deletion or edition

For creation notification it will send :
* user_id : id of the user creating the item
* content_id : internal Chamilo id of the item created
* content_url : URL to see the content
* content_title : title of the item
* course_code : code of the course in Chamilo in which the item as been created
* content_type : 'eportfolio' or 'lp'

For edition and deletion it will send :
* content_id : internal Chamilo id of the item
* content_type : 'eportfolio' or 'lp'
