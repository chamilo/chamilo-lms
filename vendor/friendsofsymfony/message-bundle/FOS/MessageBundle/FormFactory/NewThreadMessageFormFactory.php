<?php

namespace FOS\MessageBundle\FormFactory;

/**
 * Instanciates message forms
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
class NewThreadMessageFormFactory extends AbstractMessageFormFactory
{
    /**
     * Creates a new thread message
     *
     * @return Form
     */
    public function create()
    {
        $message = $this->createModelInstance();

        return $this->formFactory->createNamed($this->formName, $this->formType, $message);
    }
}
