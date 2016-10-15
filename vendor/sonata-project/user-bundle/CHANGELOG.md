# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [3.1.0](https://github.com/sonata-project/SonataUserBundle/compare/3.0.1...3.1.0) - 2016-10-14
### Changed
- The `friendsofsymfony/rest-bundle` dependency is optional again
- The `jms/serializer-bundle` dependency is optional again
- The `nelmio/api-doc-bundle` dependency is optional again
- Changed implementation of `SecurityFOSUser1Controller::loginAction`
- Changed implementation of `AdminSecurityController::loginAction`
- Changed how the error message is translated in `login.html.twig`
- Changed how the error message is translated in `base_login.html.twig`

### Fixed
- Fixed a potential null error in `SecurityFOSUser1Controller::loginAction`
- Fixed a potential empty route after calling `RegistrationFOSUser1Controller::registerAction`
- Fixed wrong route name "sonata_user_admin_resetting_request", replaced with "sonata_user_resetting_request"
- Symfony 3 security classes use in `AdminSecurityController`
- Fixed a possible security risk as noticed in this [line](https://github.com/sonata-project/SonataUserBundle/blob/88a962818dd6218379ff1439183a15647837bda0/Controller/AdminSecurityController.php#L40)

### Removed
- Internal test classes are now excluded from the autoloader
- Removed translation for 'Bad credentials' message in `SonataUserBundle.de.xliff`

## [3.0.1](https://github.com/sonata-project/SonataUserBundle/compare/3.0.0...3.0.1) - 2016-05-20
### Changed
- Admin classes extend `Sonata\AdminBundle\Admin\AbstractAdmin`
