A very lightweight yet powerful PHP state machine
=================================================

Define your states, define your transitions and your callbacks: we do the rest.
The era of hard-coded states is over!

[![Build Status](https://travis-ci.org/winzou/state-machine.svg?branch=master)](https://travis-ci.org/winzou/state-machine)

Installation (via composer)
---------------

```js
{
    "require": {
        "winzou/state-machine": "~0.1"
    }
}
```

Usage
-----

### Configure a state machine graph

In order to use the state machine, you first need to define a graph. A graph is a definition of states, transitions and optionnally callbacks ; all attached on an object from your domain. Multiple graphes can be attached to the same object.

Let's define a graph called *myGraphA* for our `DomainObject` object:

```php
$config = array(
    'graph'         => 'myGraphA', // Name of the current graph - there can be many of them attached to the same object
    'property_path' => 'stateA',  // Property path of the object actually holding the state
    'states'        => array(
        'checkout',
        'pending',
        'confirmed',
        'cancelled'
    ),
    'transitions' => array(
        'create' => array(
            'from' => array('checkout'),
            'to'   => 'pending'
        ),
        'confirm' => array(
            'from' => array('checkout', 'pending'),
            'to'   => 'confirmed'
        ),
        'cancel' => array(
            'from' => array('confirmed'),
            'to'   => 'cancelled'
        )
    ),
    'callbacks' => array(
        'guard' => array(
            'guard-cancel' => array(
                'to' => array('cancelled'), // Will be called only for transitions going to this state
                'do' => function() { var_dump('guarding to cancelled state'); return false; }
            )
        ),
        'before' => array(
            'from-checkout' => array(
                'from' => array('checkout'), // Will be called only for transitions coming from this state
                'do'   => function() { var_dump('from checkout transition'); }
            )
        ),
        'after' => array(
            'on-confirm' => array(
                'on' => array('confirm'), // Will be called only on this transition
                'do' => function() { var_dump('on confirm transition'); }
            ),
            'to-cancelled' => array(
                'to' => array('cancelled'), // Will be called only for transitions going to this state
                'do' => function() { var_dump('to cancel transition'); }
            ),
            'cancel-date' => array(
                'to' => array('cancelled'),
                'do' => array('object', 'setCancelled'),
            ),
        )
    )
);
```

So, in the previous example, the graph has 6 possible states, and those can be achieved by applying some transitions to the object. For example, when creating a new `DomainObject`, you would apply the 'create' transition to the object, and after that the state of it would become *pending*.

### Using the state machine

#### Definitions

The state machine is the object actually manipulating your object. By using the state machine you can test if a transition can be applied, actually apply a transition, retrieve the current state, etc. *A state machine is specific to a couple object + graph.* It means that if you want to manipulate another object, or the same object with another graph, *you need another state machine*.

The factory helps you to get the state machine for these couples object + graph. You give an object and a graph name to it, and it will return you the state machine for this couple. If you want to have this factory as a service in your Symfony2 application, please see the [corresponding StateMachineBundle](https://github.com/winzou/StateMachineBundle).

#### Usage

Please refer to the several examples in the `examples` folder.

#### Callbacks

Callbacks are used to guard transitions or execute some code before or after applying transitions.

Guarding callbacks must return a `bool`. If a guard returns `false`, a transition cannot be performed.


##### Credits

This library has been highly inspired by [https://github.com/yohang/Finite](https://github.com/yohang/Finite), but has taken another direction.
