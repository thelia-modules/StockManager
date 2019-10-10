<?php

namespace StockManager\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Thelia\Form\BaseForm;

class OrderCreationConfigurationForm extends BaseForm
{
    /**
     * @return string the name of you form. This name must be unique
     */
    public function getName()
    {
        return 'order_creation_configuration';
    }

    /**
     *
     * in this function you add all the fields you need for your Form.
     * Form this you have to call add method on $this->formBuilder attribute :
     *
     */
    protected function buildForm()
    {
        $this->formBuilder
            ->add('decrementStockModules', TextType::class);
    }
}