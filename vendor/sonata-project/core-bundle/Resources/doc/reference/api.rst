.. index::
    double: Api; Definition

Api
===

The bundle comes with a handy ``FormHelper::removeField`` method which can be used to remove form's fields
if the related submitted data is not provided. The Form Component's default behavior is to set ``null`` if a form
field is defined but no data submitted for this particular field. This is quite annoying while building an API and the
client only sent partial data to update an entity.

.. code-block:: php

    <?php

    $category = $id ? $this->getCategory($id) : null;

    $form = $this->formFactory->createNamed(null, 'sonata_classification_api_form_category', $category, array(
        'csrf_protection' => false
    ));

    FormHelper::removeFields($request->request->all(), $form);

    $form->bind($request);

    if ($form->isValid()) {
        // ...
    }

The call need to be done before the ``bind`` function as you cannot manipulate the form after the binding.
