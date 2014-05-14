<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Provider;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Routing\Loader\AnnotationFileLoader;
use Symfony\Component\Routing\Loader\AnnotationClassLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Sensio\Bundle\FrameworkExtraBundle\Routing\AnnotatedRouteControllerLoader;

use Doctrine\Common\Annotations\AnnotationReader;
use ChamiloLMS\Middleware\CourseMiddleware;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ReflectionControllerProvider
 * Parses the controllers classes in order to transform the
 * @route and @method annotations into routes.
 * @package ChamiloLMS\Provider
 */
class ReflectionControllerProvider implements ControllerProviderInterface
{
    private $controllerName;

    /**
     * @param string $controllerName
     */
    public function __construct($controllerName)
    {
        $this->controllerName = $controllerName;
    }

    /**
     * @param Application $app
     * @return \Silex\ControllerCollection
     */
    public function connect(Application $app)
    {
        /** @var \Silex\ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        // Routes are already cached using Flint

        if (file_exists($app['path.temp'].'ProjectUrlMatcher.php')) {
            return $controllers;
        }

        $reflection = new \ReflectionClass($app[$this->controllerName]);
        $className = $reflection->getName();

        // Needed in order to get annotations
        $annotationReader = new AnnotationReader();
        //$classAnnotations = $annotationReader->getClassAnnotations($reflection);
        $routeAnnotation = new Route(array());
        $methodAnnotation = new Method(array());

        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        /*$app['dispatcher']->addListener(KernelEvents::REQUEST, function() use ($path) {
            unlink($path);
        });*/

        foreach ($methods as $method) {
            $methodName = $method->getName();
            $controllerName = $this->controllerName.':'.$methodName;

            // Parse only function with the "Action" suffix

            if (strpos($methodName, 'Action') === false) {
                continue;
            }

            // Getting all annotations
            $routeObjects = $annotationReader->getMethodAnnotations($method);

            /** @var Method $routeObject */
            $methodObject = $annotationReader->getMethodAnnotation($method, $methodAnnotation);

            $methodsToString = 'GET';
            if ($methodObject) {
                $methodsToString = implode('|', $methodObject->getMethods());
            }

            /*$print = false;
            if ($controllerName == 'course_home.controller:indexAction') {
                $print = true;
                //var_dump($routeObjects);
            }*/

            /** @var Route $routeObject */
            $routes = array();

            foreach ($routeObjects as $routeObject) {
                if ($routeObject && is_a($routeObject, 'Symfony\Component\Routing\Annotation\Route')) {
                    $name = $routeObject->getName();

                    $routeName = $controllerName;
                    // If route already exists add a "_XX" where XX is an int.
                    $bindName = $controllerName;
                    if (in_array($bindName, $routes)) {
                        $counter = 1;
                        while (in_array($bindName, $routes)) {
                            $bindName = $controllerName.'_'.$counter;
                            $counter++;
                        }
                    }

                    $match = $controllers->match(
                        $routeObject->getPath(),
                        $routeName
                    )
                        ->method($methodsToString)
                        ->before($this->controllerName . ':before');

                    $routes[] = $bindName;

                    // Setting requirements.
                    $req = $routeObject->getRequirements();

                    if (!empty($req)) {
                        foreach ($req as $key => $value) {
                            $match->assert($key, $value);
                        }
                    }

                    // Setting defaults.
                    $defaults = $routeObject->getDefaults();
                    if (!empty($defaults)) {
                        foreach ($defaults as $key => $value) {
                            $match->value($key, $value);
                        }
                    }

                    // Setting options
                    //$options = $routeObject->getOptions();

                    // Adding bind
                    $match->bind($bindName);

                    // Adding alias if exists.
                    if (!empty($name)) {
                        $match->bind($name);
                    }
                }
            }
        }

        return $controllers;
    }
}
