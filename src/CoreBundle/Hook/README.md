Hook Management plugin for Chamilo LMS
=======================================
Enable hooks in Chamilo to allow plugins and core to extend current features and
watch for certain events.

The Hooks structure is based on the Observer pattern

The base structure is composed of 3 Interfaces
* HookEvent: will call the hook methods in Chamilo code
* HookObserver: will be executed when a Hook event is called
* HookManagement: manages hooks, creation, instantiation, persistence and
 connection to the database



From version 1.10.x, the following Hooks (or more) exist:


|Number| Directory                         | EventClass     | ObserverInterface               | Reference                 |
|------|-----------------------------------|----------------|---------------------------------|---------------------------|
|     1| /main/inc/lib/usermanager.lib.php | HookCreateUser | HookCreateUserObserverInterface | UserManager::createUser() |
|     2| /main/inc/lib/usermanager.lib.php | HookUpdateUser | HookUpdateUserObserverInterface | UserManager::updateUser() |
|     3| /main/admin/index.php             | HookAdminBlock | HookAdminBlockObserverInterface | ADMIN BLOCK               |

# What do I need to use Hooks?

You need to create a class extending the `HookObserver` class and implement any
(or many) Hook Observer Interfaces, e.g. `HookCreateUserObserverInterface`.
An observer can implement many Hook Observer Interfaces.
This was developed to allow plugins to have a unique Hook Observer class.
Don't forget to add your Hook Observer class to the autoload file (vendor/composer/autoload_classmap.php).

# How to add MyHookObserver to my plugin?

When installing your plugin (or other functions) you should call the attach 
method from a specific Hook Observer class, e.g. `HookCreateUser` class
```
$myHookObserver = MyHookObserver::create();
HookCreateUser::create()->attach($myHookObserver);
```

# How to detach MyHookObserver from inside my plugin?

To detach the HookObserver, it should be detached from a specific Hook Event class
```
$myHookObserver = MyHookObserver::create();
HookCreateUser::create()->detach($myHookObserver);
```

# How to add HookEvents to the Chamilo code (add the possibility to be hooked)?

To expand Hooks in Chamilo you should:
1. Identify an event that could be customized through a plugin
2. Create an interface for the Hook Event and the Hook Observer.
 The names should be like the Hooks interfaces already created,
 with The Pattern: HookXyzEventInterface and HookXyzObserverInterface.
 e.g. Hook event: `HookUpdateUserEventInterface`, Hook observer: `HookUpdateUserObserverInterface`
3. Add at least one notify method to Hook Event Interface and update method to 
 Hook Observer Interface
4. Create a class extending the `HookEvent` class and implementing your Hook 
 Event Interface
5. Complete the notify method calling the Hook Observer update
6. Add your Interfaces and Class to the autoload file (vendor/composer/autoload_classmap.php)
7. Test your hook. If your Observer requires data, you can use the data property
 from Hook Event
