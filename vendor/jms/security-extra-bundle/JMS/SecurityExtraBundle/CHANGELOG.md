This document details all changes between different versions of JMSSecurityExtraBundle:

1.4
---
- allowed @SecureParam to be used on class-level (similar to @PreAuthorize)
- added the ability to exclude certain attributes from IDDQD-checks
- improved error messages for security expressions
- several annotations now also accept arrays of roles in addition to comma-separated strings


1.3
---
- added a reverse interpreter for expressions
- added logging of votes for expressions
- added a generic expression function compiler for service callbacks making it
  easier to create custom expression functions


1.2
---

- added a secure random number generator service
- several bug fixes


1.1
---

- The configuration option "secure_controllers" has been removed. This setting is
  now automatically enabled, but it requires the JMSDiExtraBundle.

- The dependencies of this bundle have changed:
  
    * The metadata library 1.1 version is now required instead of the 1.0 version
      (if you are using the Standard Edition, just change the "version=origin/1.0.x" 
      line from your deps file to "version=1.1.0").
    * The JMSAopBundle is now required. For installation instructions, please see
      https://github.com/schmittjoh/JMSAopBundle
    * The JMSDiExtraBundle is now required if you want to secure your non-service
      controllers (if you only have service controllers, you don't need it). For
      installation instructions, see https://github.com/schmittjoh/JMSDiExtraBundle

- The attribute "IS_IDDQD" has been renamed to "ROLE_IDDQD"

- A powerful expression-based authorization language has been added which works
  in combination with the existing voting system. Since it is much more powerful
  than the built-in voters, and also much faster, you are highly encouraged to
  migrate your existing authorization rules to expressions, and eventually disable 
  the built-in voters entirely. Some examples for how to convert simple attributes
  to their equivalent expressions are listed below:
  
    * IS_AUTHENTICATED_ANONYMOUSLY -> "permitAll"
    * IS_AUTHENTICATED_REMEMBERED -> "isAuthenticated()"
    * IS_AUTHENTICATED_FULLY -> "isFullyAuthenticated()"
    * ROLE_FOO -> "hasRole('ROLE_FOO')"

- The ability to configure method access control (e.g. for controller actions)
  in the DI configuration has been added. Note that for non-service controllers
  the JMSDiExtraBundle is required.

- The "is_expr_granted" Twig function has been added if you want to check an
  expression from a Twig template.


1.0
---

Initial release  
