UPGRADE
=======

Upgrading from 1.x to 2.0
-------------------------

* Attachment fixtures are now objects containing up to two keys. `metadata`
  contains the JSON encoded attachment while `content`, if present, denotes
  the raw attachment content.
