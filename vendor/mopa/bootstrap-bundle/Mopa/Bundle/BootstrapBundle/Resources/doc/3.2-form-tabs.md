Form Tabs
================

There are often times when you need to put parts of a long form into tabs, this
will allow you to use a new form type called "tab" in order to accomplish this.

Here's an example form:

```php
public function buildForm(FormBuilderInterface $builder, array $options)
{
    $about = $builder->create('about', 'tab', array(
        'label' => 'About',
        'icon' => 'pencil',
        'inherit_data' => true,
    ));

    $about
        ->add('first')
        ->add('last');

    $social = $builder->create('social', 'tab', array(
        'label' => 'Social'
        'icon' => 'user',
    ));

    $social
        ->add('facebook')
        ->add('twitter');

    /**
     * Add both tabs to the main form
     */
    $builder
        ->add($about)
        ->add($social);
}

public function setDefaultOptions(OptionsResolverInterface $resolver)
{
    $resolver->setDefaults(array(
        'tabs_class' => 'nav nav-pills nav-stacked',
        'data_class' => 'Acme\Bundle\WebsiteBundle\Entity\User',
    ));
}
```

Rendering the entire form is as simple as using `form_widget(form)`.

- You change the class that gets rendered on the tabs ul by specifying
  the `tabs_class` option.
- You can add an icon to the tabs by specifying the `icon` option. It automatically
  prefixes the icon with "icon-".

Working with entities
====================

If you are using the form to put data into an object and not just an array, you'll need
to supply the `data_class` option like you normally do. However, unless each of your
tabs is a related entity - If your entity was a User, which had an Address entity attached,
you could just add a tab called `address` and then add children to it - You will need to use
`inherit_data` (previously `virtual`) so that the form knows not to look for a property
that is the same name as your tab.

In the example above, `social` might be an array, or a related entity so it does not need
the `inherit_data`, however, the "About" our user is actually party of the User entity
so we need to make sure we set `inherit_data` to true.


Rendering
====================


If you do not want the whole thing to
be rendered at once, you can use the `form_tabs` function on the tabs' parent
form:

```jinja
<div class="row-fluid">
    <div class="span4">
        {{ form_tabs(form) }}
    </div>

    <div class="span8">
        {{ form_widget(form) }}
    </div>
</div>
```

*Be Aware:* If you use `form_widget` before `form_tabs` it will also render the
tabs, and you will not be able to render them again.

Finally, if you need full control over the tabs, you can access them like:

```jinja
{% for tab in form.vars.tabs %}
   {# ... #}
{% endfor %}
```

You can also use form theming to change the way the tabs are rendered:

```jinja
{% form_theme _self %}

{% block tabs_widget %}
<div class="{{ form.vars.attr.class }}">
    {% for tab in form.vars.tabs %}
        <a data-toggle="tab" href="#{{ tab.id }}">
            {{ tab.label }} <i class="icon-{{ tab.icon }}"></i>
        </a>
    {% endfor %}
</div>
{% endblock %}
```
