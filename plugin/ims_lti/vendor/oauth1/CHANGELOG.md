### 2016.02.10
----
* Added composer.json
* Separated classes to separate files

### 2008.08.04
----
* Added LICENSE.txt file with MIT license, copyright owner is perhaps
	dubious however.

### 2008.07.22
----
* Change to encoding to fix last change to encoding of spaces

### 2008.07.15
----
* Another change to encoding per:   
	[http://groups.google.com/group/oauth/browse_thread/thread/d39931d39b4af4bd](http://groups.google.com/group/oauth/browse_thread/thread/d39931d39b4af4bd)
* A change to port handling to better deal with https and the like per:  
  [http://groups.google.com/group/oauth/browse_thread/thread/1b203a51d9590226](http://groups.google.com/group/oauth/browse_thread/thread/1b203a51d9590226)
* Fixed a small bug per:  
	[https://github.com/jtsternberg/oauth/issues/26](https://github.com/jtsternberg/oauth/issues/26)
* Added missing base_string debug info when using RSA-SHA1
* Increased size of example endpoint input field and added note about
  query strings

### 2009-2011.03.28
----
* Heaps of bug-fixes
* Introduction of PHPUnit testcases (which aided in above mentioned bug-fixes)
* Added support Revision A auth flows.
* Possibly more, I've lost track..

### 2001.03.29
----
* Fixed issue with hosts not being normalized correctly  
  [http://tools.ietf.org/html/rfc5849#section-3.4.1.2](http://tools.ietf.org/html/rfc5849#section-3.4.1.2)  
  [https://github.com/jtsternberg/oauth/issues/176](https://github.com/jtsternberg/oauth/issues/176)  
  [https://github.com/jtsternberg/oauth/issues/187](https://github.com/jtsternberg/oauth/issues/187)  
* Changed signature comparing to be timing insensitive  
  [https://github.com/jtsternberg/oauth/issues/178](https://github.com/jtsternberg/oauth/issues/178)
* Fixed issue with Host header on some servers on non-standard port includes the port-number
  [https://github.com/jtsternberg/oauth/issues/170](https://github.com/jtsternberg/oauth/issues/170)  
  [https://github.com/jtsternberg/oauth/issues/192](https://github.com/jtsternberg/oauth/issues/192)  
