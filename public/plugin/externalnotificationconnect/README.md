# External Notification Connect

Activate external notification system, that will send a notification to an external REST webservice on a specific Chamilo's action trigger.

At the moment it will send notifications on :

* Learning path creation (Chamilo LP or Scorm import)
* Portfolio post creation, deletion or edition

For creation notifications, it will send :

* user_id : id of the user creating the item
* content_id : internal Chamilo id of the item created
* content_url : URL to see the content
* content_title : title of the item
* course_code : code of the course in Chamilo in which the item as been created
* content_type : 'eportfolio' or 'lp'

For editions and deletions, it will send :

* content_id : internal Chamilo id of the item
* content_type : 'eportfolio' or 'lp'
