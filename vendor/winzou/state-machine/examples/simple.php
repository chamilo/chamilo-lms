<?php

/*
 * This file is part of the StateMachine package.
 *
 * (c) Alexandre Bacco
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require '../vendor/autoload.php';
require 'DomainObject.php';

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
            'confirm-date' => array(
                'on' => array('confirm'),
                'do' => array('object', 'setConfirmedNow'), // 'object' will be replaced by the object undergoing the transition
            ),
        )
    )
);

// Our object
$object = new DomainObject;

// State machine is created being given an object and a config
$stateMachine = new \SM\StateMachine\StateMachine($object, $config);

// Current state is checkout
var_dump($stateMachine->getState());

// Return true, we can apply this transition
var_dump($stateMachine->can('create'));

// Return true, this transitions is applied
// In addition, callback 'from-checkout' is called
var_dump($stateMachine->apply('create'));

// Current state is pending
var_dump($stateMachine->getState());

// All possible transitions for pending state are just "confirm"
var_dump($stateMachine->getPossibleTransitions());

// Return false, this transition is not applied
// 2nd argument is soft mode: it returns false instead of throwing an exception
var_dump($stateMachine->apply('cancel', true));

// Current state is still pending
var_dump($stateMachine->getState());

// Return true, this transition is applied
// In addition, callback 'on-confirm' is called
// And callback 'confirm-date' calls the method 'setConfirmedNow' on the object itself
var_dump($stateMachine->apply('confirm'));

// Current state is confirmed
var_dump($stateMachine->getState());

// Returns false, as it is guarded
var_dump($stateMachine->can('cancel'));

// Current state is still confirmed
var_dump($stateMachine->getState());
