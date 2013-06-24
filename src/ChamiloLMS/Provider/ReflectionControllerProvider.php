<?php

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

class ReflectionControllerProvider implements ControllerProviderInterface
{
    private $controllerName;

    function __construct($controllerName)
    {
        $this->controllerName = $controllerName;
    }

    function connect(Application $app)
    {
        /** @var \Silex\ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        //$reflection = new \ReflectionClass($this->class);
        $reflection = new \ReflectionClass($app[$this->controllerName]);

        $annotationReader = new AnnotationReader();
        //$classAnnotations = $annotationReader->getClassAnnotations($reflection);
        $routeAnnotation = new Route(array());
        $methodAnnotation = new Method(array());
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $methodName = $method->getName();
            if (in_array($methodName, array('__construct'))) {
                continue;
            }

            /** @var Route $routeObject */
            $routeObject = $annotationReader->getMethodAnnotation($method, $routeAnnotation);

            /** @var Method $routeObject */
            $methodObject = $annotationReader->getMethodAnnotation($method, $methodAnnotation);

            $methodsToString = 'GET';
            if ($methodObject) {
                $methodsToString = implode('|', $methodObject->getMethods());
            }

            $controllers->match($routeObject->getPath(), $this->controllerName.':'.$methodName, $methodsToString);
        }

        return $controllers;
    }

    private function adjustPath($path)
    {
        $path = lcfirst($path);
        $path = ('index' === $path) ? '' : $path;
        $path = '/'.$path;

        return $path;
    }
}
