<?php

namespace StockManager\Form;

use StockManager\StockManager;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Thelia\Form\BaseForm;
use Thelia\Model\ModuleQuery;

class OrderCreationConfigurationForm extends BaseForm
{
    /**
     *
     * in this function you add all the fields you need for your Form.
     * Form this you have to call add method on $this->formBuilder attribute :
     *
     */
    protected function buildForm()
    {
        $ModuleList = (new ModuleQuery)->filterByType('3')->find();

        foreach ($ModuleList as $module)
        {
            $this->formBuilder
                ->add(
                    $module->getCode(),
                    TextType::class,
                    []
                );
        }
    }
}