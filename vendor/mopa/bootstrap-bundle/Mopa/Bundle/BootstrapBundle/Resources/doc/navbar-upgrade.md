Upgrading Your Navbars
====================

We've changed the way that Navbars work. The old way required you to extend
another class in order to generate the Navbar. The new way is to use a
KnpMenu extension in order to generate the Navbar in the same way that
KnpMenu works natively.

## The Old Way

Your old configuration might have looked like this:

```yaml
services:
    sternenbund.navbar:
        class: '%mopa_bootstrap.navbar.generic%'
        scope: request
        arguments:
            - { leftmenu: @sternenbund.navbar_main_menu=, rightmenu: @sternenbund.navbar_right_menu= }
            - {}
            - { title: "Sternenbund", titleRoute: "mopa_bootstrap_welcome", fixedTop: true }
        tags:
            - { name: mopa_bootstrap.navbar, alias: frontendNavbar }
```

Your navbar class will probably look something like this:

```php
use Mopa\Bundle\BootstrapBundle\Navbar\AbstractNavbarMenuBuilder;

class NavbarMenuBuilder extends AbstractNavbarMenuBuilder
{
    public function createMainMenu()
    {
        $menu = $this->createNavbarMenuItem();
        $menu->addChild('Shipdev', array('route' => 'shipdev'));

        $dropdown = $this->createDropdownMenuItem($menu, "Dropdown");
        $dropdown->addChild('Dropdown Item 1', array('route' => 'dropdown_route'));

        return $menu;
    }
}
```

And you would have rendered your Navbar by doing this:

```jinja
{{ mopa_bootstrap_navbar('frontendNavbar') }}
```


## The New Way

Firstly, you can completely get rid of your Navbar Generic class in your services
config. (This is the definition with %mopa_bootstra.navbar.generic% as the class)
We will directly use these when rendering the Navbar now, you no longer need this.

Second, you will want to use the new method of declaring your menu, this is how
the menu would look now:

```php
<?php

// Namespace should be in the "Menu" folder so we can use Knp's notation
namespace Acme\Bundle\AcmeBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class Builder extends ContainerAware
{
    public function mainMenu(FactoryInterface $factory, array $options)
    {
        // This will add the proper classes to your UL
        // Use push_right if you want your menu on the right
        $menu = $factory->createItem('root', array(
            'navbar' => true,
            'push_right' => true,
        ));

        // Regular menu item, no change
        $menu->addChild('Shipdev', array('route' => 'shipdev'));

        // Create a dropdown
        $dropdown = $menu->addChild('Dropdown', array(
            'dropdown' => true,
            'caret' => true,
        ));

        // Add child to dropdown, still normal KnpMenu usage
        $dropdown->addChild('Dropdown Item 1', array('route' => 'dropdown_route'));

        return $menu;
    }
}
```

Now, rendering your menu requires the use of the Twig `embed` tag. You can
add as many menues as you would like here. If you used a leftmenu and a rightmenu
from the previous method, you should use the `push_right` option to create your
"rightmenu."

```jinja
{% embed '@MopaBootstrap/Navbar/navbar.html.twig' with { fixedTop: true, inverse: true } %}
    {% block brand %}
        <a class="navbar-brand" href="#">My Brand</a>
    {% endblock %}

    {% block menu %}
        {{ mopa_bootstrap_menu('AcmeBundle:Builder:mainMenu') }}
        {{ mopa_bootstrap_menu('sternenbund.navbar_main_menu') }}
    {% endblock %}
{% endembed %}
```

You can use either nomenclature to create your menu. Since you no longer need
to extend the abstract navbar class, you may want to switch to the KnpMenu
notation. You do not even need to use the template we provide you, you could
also write the whole navbar yourself. We just take care of adding the proper
classes to the ul, li's, a's, etc. This gives you more customization over the
Navbar.