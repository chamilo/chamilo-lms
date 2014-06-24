Form Extensions
===============

Make use of FormExtensions
--------------------------

This bundle extends the Symfony Form Component via its native way to achieve having several more attributes on several form components.

Have a look into the examples in the sandbox:

 * http://bootstrap.mohrenweiserpartner.de/mopa/bootstrap/forms/examples
 * https://github.com/phiamo/MopaBootstrapSandboxBundle/tree/master/Form/Type


### Using bootstrap for Theming


Forms are activated by default for your whole project if you do not want to have this behaviour you can disable it by setting the templating config option to false in app/config.yml.
There are a bunch of other config variabled to control the templating behaviour globally.
You can change them globally, on a per form basis and per field.

``` yaml
mopa_bootstrap:
    form:
        templating: false # default is true
        render_fieldset: true # default is true
        render_collection_item: true # default is true
        show_legend: true # default is true
        show_child_legend: false # default is false
        render_required_asterisk: true # default is true
        checkbox_label: 'both' # default is both (label|widget|both)
        error_type: 'block' # default is null
        collection:
            widget_remove_btn:
                attr:
                    class: btn
                icon: null
                icon_color: null
            widget_add_btn:
                attr:
                    class: btn
                icon: null
                icon_color: null
```

Or include the fields.html.twig in your template for a certain form:

``` jinja
{% form_theme myform 'MopaBootstrapBundle:Form:fields.html.twig' %}
```

If you want the default bootstrap forms instead of horizontal add this to your config.yml

``` yaml
mopa_bootstrap:
    form:
        horizontal_label_class: ~
        horizontal_input_wrapper_class: ~
```

Form Legends
------------

Every Form component representing a Form, not a Field, (e.g. subforms, widgets of type date beeing expanded, etc)
has now a attribute called show_legend which controls wether the "form legend" is shown or not.

This can be controlled globally by adapting your config.yml:

``` yaml
mopa_bootstrap:
    form:
        show_legend: false # default is true
```

Now you can tell a specific form to have the legend beeing shown by using:

``` php
public function buildForm(FormBuilder $builder, array $options)
{
    $builder->setAttribute('show_legend', true);
    // ...
```


Child Form Legends
------------------

In symfony2 you can easily glue different forms together and build a nice tree.
Normally there is a label for every sub form (including special widgets like date expanded, radio button expanded, etc)
with the name of the Subform rendered.
This might make sense or not. I decided to disable this by default, but enabling it is easy:

To enable it globally use:

``` yaml
mopa_bootstrap:
    form:
        show_legend: false # default is true
```

If you just want to have it in a special form do it like that:

``` php
// e.g. a form only consisting of subforms
public function buildForm(FormBuilder $builder, array $options)
{
    $builder->setAttribute('show_legend', false); // no legend for main form
    $child = $builder->create('user', new SomeSubFormType(), array('show_child_legend' => true)); // but legend for this subform
    $builder->add($child);
    // ...
```

Field Labels
------------

You have the option to remove a specific field label by setting label_render to false

``` php
       $builder
            ->add('somefield', null, array(
                'label_render' => false
            ))
```

Since symfony 2.1 the label_attr is included in the base, to add special attr to the labels

Form Field Help
---------------

Every Form Field component representing a Field, not a Form, (e.g. inputs, textarea, radiobuttons beeing not expanded etc)
has several new attributes:

  - help_block:  beeing shown under the element
  - help_label:  beeing shown under the label of the element

Now you can easily add a help text at different locations:

``` php
// e.g. a form needing a lot of help
public function buildForm(FormBuilder $builder, array $options)
{
    $builder
        ->add('shortDescription', 'textarea', array(
            "attr" => array("rows"=>3, 'class'=>'xxlarge'),
            "help_block"=>"This is the short descriptions shown somewhere"
        ))
        ->add('longDescription', null, array(
            "attr"=>array("rows" => 10),
            "help_label"=>"Please enter a very very long description"
        ))
    ;
    //...
```

Widget Addons
-------------
You can integrate Twitter Bootstrap's form addons, you have the choice between `icon` or `text` options:

```php
public function buildForm(FormBuilder $builder, array $options)
{
    $builder
        ->add('price', null, array(
            "widget_addon_append" => array(
                "icon"     => "home",
            ),
            "widget_addon_prepend" => array(
                "text"     => "My text",
            )
        ))
    ;
    //...
```

Note: To get the addons working, i had to increase max nesting level of xdebug to 200.


### Form Field Prefix / Suffix

There are also suffix and prefix attributes for the widgets:

``` php
// e.g. a form where you want to give in a price
public function buildForm(FormBuilder $builder, array $options)
{
    $builder
        ->add('price', null, array(
            "attr" => array(
                "class"=>"span1",
            ),
            "widget_suffix"=>"€"
        ))
    ;
    //...
```


Form Errors
-----------

Generally you may want to define your errors to be displayed inline OR block (see bootstrap) you may define it globally in your conf:

``` yaml
mopa_bootstrap:
    form:
        error_type: block # or inline which is default

```

Or on a special Form:

``` php
public function buildForm(FormBuilder $builder, array $options)
{
    $builder
    //...
            ->setAttribute('error_type', "inline")
    ;
    //...
```

Or on a special field:

``` php
public function buildForm(FormBuilder $builder, array $options)
{
    $builder
    //...
           ->add('country', null, array('error_type'=>'block'))
    ;
    //...
```

In some special cases you may also want to not have a form error but an field error
so you can use error delay, which will delay the error to the first next field rendered in a child form:

``` php
public function buildForm(FormBuilder $builder, array $options)
{
    $builder
    //...
            ->add('plainPassword', 'repeated', array(
                   'type' => 'password',
                   'error_delay'=>true
            ))
    ;
    //...
```

Widget Attrs
------------

// Thanks to JohanLopes and PR #105:
There are a bunch of other form extenstions, so you can explicitly set the classes of the control tags,
by default there is only the control-group and the error (if the widget has error) classes rendered into it :

``` php
       $builder
            ->add('somefield', null, array(
                'widget_control_group_attr' => array('class'=>'mycontrolgroupclass'),
                'widget_controls_attr' => array('class'=>'mycontrolsclass'),
                'label_attr' => array('class'=>'mylabelclass') // this is new in sf2.1 form component
            ))
```

will result in

``` html
<div id="myWidgetName_control_group" class="mycontrolgroupclass control-group">
    <label class="mylabelclass required control-label">My Label</label>
    <div class="mycontrolsclass controls">

    ...
```

Buttons
-------

It's possible to add icon tags to buttons which are generated via the form component.
This works for the field types 'button' as well as 'submit' and 'reset'.
In order to do this, use the properties icon and icon_color:

``` php
$builder
    ->add(
        'save',
        'submit',
        [
            'icon'       => 'save',
            'icon_color' => '#FF00FF'
        ]
    );
```

results in:

``` html
<button class="btn" ... type="submit">
    <i class="icon-save" style="color: #FF00FF;"></i> Save
</button>
```

Collections
-----------

Look into the more detailed doc:

https://github.com/phiamo/MopaBootstrapBundle/blob/master/Resources/doc/3.1-form-collections.md


