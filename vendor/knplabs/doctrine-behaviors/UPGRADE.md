# 1.0.2 to 1.0.x-dev

Most occurences of "listener" have been replaced with "subscriber" to honor a
subtle difference between the two that is explained
[in the documentation](http://doctrine-orm.readthedocs.org/en/latest/reference/events.html#listening-and-subscribing-to-lifecycle-events).
If you use translatable, you should map your translation classes `id` fields,
the `id` mapping is deprecated and will be removed in version 2.0
If you decided to create your own subscriber by extending a class from this library,
you might have to refactor your code because of this.
Likewise, DI parameters (for class names), and service ids have changed.
