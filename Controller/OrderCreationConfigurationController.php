<?php

namespace StockManager\Controller;

use Exception;
use StockManager\Form\OrderCreationConfigurationForm;
use StockManager\StockManager;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Template\ParserContext;

#[Route('/admin/module/StockManager', name: 'stock_manager_order_creation_config_')]
class OrderCreationConfigurationController extends BaseAdminController
{
    #[Route('/OrderCreationConfiguration', name: 'save', methods: 'POST')]
    public function saveAction(ParserContext $parserContext)
    {
        if (null !== $response = $this->checkAuth(array(), 'StockManager', AccessManager::VIEW)) {
            return $response;
        }

        $configurationForm = $this->createForm(OrderCreationConfigurationForm::getName());

        try {
            $result = [];
            $data = $this->validateForm($configurationForm)->getData();

            foreach ($data as $field)
            {
                if ($field !== null)
                {
                    if ((int)$field !== 0)
                    {
                        $result[] = $field;
                    }
                }
            }

            StockManager::setConfigValue(StockManager::DECREMENT_STOCK_MODULE_CONFIG_KEY, implode(',', $result));

            return $this->generateSuccessRedirect($configurationForm);
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }

        $configurationForm->setErrorMessage($error_message);

        $parserContext
            ->addForm($configurationForm)
            ->setGeneralError($error_message);

        return $this->generateErrorRedirect($configurationForm);
    }
}