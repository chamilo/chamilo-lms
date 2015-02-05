Hook Management plugin for Chamilo LMS
=======================================
Enable hooks in Chamilo to allow plugin to extend functionality.

Hooks structure is based on Observer pattern

The base structure is composed by 3 Interfaces
* HookEvent: This will call the hook methods in Chamilo code
* HookObserver: This will be executed when a Hook event is called
* HookManagement: Manage hooks, creation, instantiation, persistence, connection to database and is implemented to a Plugin



On this version exists Hooks for:


|Number| Directory                         | EventClass     | ObserverInterface               | Reference                 |
|------|-----------------------------------|----------------|---------------------------------|---------------------------|
|     1| /main/inc/lib/usermanager.lib.php | HookCreateUser | HookCreateUserObserverInterface | Usermanager::createUser() |
|     2| /main/inc/lib/usermanager.lib.php | HookUpdateUser | HookUpdateUserObserverInterface | Usermanager::updateUser() |
|     3| /main/admin/index.php             | HookAdminBlock | HookAdminBlockObserverInterface | ADMIN BLOCK               |

# What I need to use Hook?

You need to create a class extending `HookObserver` class
and implementing any (or many) Hook Observer Interfaces, e.g. `HookCreateUserObserverInterface`.
An observer can implement many Hook observer interface.
This was done to allow Plugin to have a unique Hook Observer class
Dont forget to add your Hook Observer class to autoload file

# How to add MyHookObserver to my plugin?

Before this, the hook management plugin must be enabled

When installing your plugin (or other functions) you should call
the attach method from an specific Hook Observer class, e.g. `HookCreateUser` class
```
$myHookObserver = MyHookObserver::create();
HookCreateUser::create()->attach($myHookObserver);
```

# How to remove MyHookObserver to my plugin?

For remove the HookObserver, this should be detached from specific Hook Event class
```
$myHookObserver = MyHookObserver::create();
HookCreateUser::create()->detach($myHookObserver);
```

# How to add HookEvents to Chamilo?

To expand Hook in Chamilo you should do:
1. Identify an event could be customized by a plugin
2. Create an interface for the Hook Event and and Hook Observer.
 The names should be like the Hooks interfaces already created,
 with The Pattern: HookXyzEventInterface and HookXyzObserverInterface,
 e.g. Hook event: `HookUpdateUserEventInterface`, Hook observer: `HookUpdateUserObserverInterface`
3. Add at least a notify method to Hook Event Interface and update method to Hook Observer Interface
4. Create a class extending `HookEvent` class and implementing your Hook Event Interface
5. Complete the notify method calling to Hook observer update
6. Add your Interfaces and Class to autoload file
7. Test your hook. if your Observer require data, you can use the data property from Hook Event