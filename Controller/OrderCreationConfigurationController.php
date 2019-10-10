<?php

namespace StockManager\Controller;

use StockManager\Model\StockOperation;
use StockManager\StockManager;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;

class OrderCreationConfigurationController extends BaseAdminController
{
    public function saveAction()
    {
        if (null !== $response = $this->checkAuth(array(), 'StockManager', AccessManager::VIEW)) {
            return $response;
        }

        $configurationForm = $this->createForm('order_creation_configuration');

        $form = $this->validateForm($configurationForm, "POST");

        // Get the form field values
        $data = $form->getData();

        StockManager::setConfigValue(StockManager::DECREMENT_STOCK_MODULE_CONFIG_KEY, implode(',', $data['decrementStockModules']));

        return $this->generateSuccessRedirect($configurationForm);
    }
}