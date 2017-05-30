# Zend\\Server\\Reflection

## Introduction

`Zend\Server\Reflection` provides a standard mechanism for performing function and class
introspection for use with server classes. It is based on *PHP* 5's Reflection *API*, augmenting it
with methods for retrieving parameter and return value types and descriptions, a full list of
function and method prototypes (i.e., all possible valid calling combinations), and function or
method descriptions.

Typically, this functionality will only be used by developers of server classes for the framework.

## Usage

Basic usage is simple:

```php
$class    = Zend\Server\Reflection::reflectClass('My\Class');
$function = Zend\Server\Reflection::reflectFunction('my_function');

// Get prototypes
$prototypes = $function->getPrototypes();

// Loop through each prototype for the function
foreach ($prototypes as $prototype) {

    // Get prototype return type
    echo "Return type: ", $prototype->getReturnType(), "\n";

    // Get prototype parameters
    $parameters = $prototype->getParameters();

    echo "Parameters: \n";
    foreach ($parameters as $parameter) {
        // Get parameter type
        echo "    ", $parameter->getType(), "\n";
    }
}

// Get namespace for a class, function, or method.
// Namespaces may be set at instantiation time (second argument), or using
// setNamespace()
$class->getNamespace();
```

`reflectFunction()` returns a `Zend\Server\Reflection\Function` object; `reflectClass()` returns a
`Zend\Server\Reflection\Class` object. Please refer to the *API* documentation to see what methods
are available to each.
