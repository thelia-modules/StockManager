<?php

namespace StockManager\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Thelia\Form\BaseForm;

class StockOperationForm extends BaseForm
{
    /**
     *
     * in this function you add all the fields you need for your Form.
     * Form this you have to call add method on $this->formBuilder attribute :
     *
     */
    protected function buildForm()
    {
        $this->formBuilder
            ->add('source_statuses', TextType::class)
            ->add('target_statuses', TextType::class)
            ->add('payment_modules', TextType::class)
            ->add('delivery_modules', TextType::class)
            ->add('operation', TextType::class);
    }
}