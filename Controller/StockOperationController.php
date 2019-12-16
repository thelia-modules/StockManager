<?php

namespace StockManager\Controller;

use Propel\Runtime\ActiveQuery\Criteria;
use StockManager\Model\StockOperation;
use StockManager\Model\StockOperationDeliveryModule;
use StockManager\Model\StockOperationPaymentModule;
use StockManager\Model\StockOperationQuery;
use StockManager\Model\StockOperationSourceStatus;
use StockManager\Model\StockOperationSourceStatusQuery;
use StockManager\Model\StockOperationTargetStatus;
use StockManager\StockManager;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Security\AccessManager;
use Thelia\Model\MessageQuery;
use Thelia\Model\ModuleQuery;
use Thelia\Model\OrderStatusQuery;
use Thelia\Model\Tools\ModelCriteriaTools;
use Thelia\Module\BaseModule;

class StockOperationController extends BaseAdminController
{
    /**
     * @param array $params
     * @return Response
     */
    public function viewAction($params = array())
    {
        if (null !== $response = $this->checkAuth(array(), 'StockManager', AccessManager::VIEW)) {
            return $response;
        }

        $stockOperations = StockOperationQuery::create()
            ->find();

        $orderStatusQuery = OrderStatusQuery::create();

        ModelCriteriaTools::getI18n(
            true,
            $this->getSession()->getLang()->getId(),
            $orderStatusQuery,
            $this->getSession()->getLang()->getLocale(),
            ['title'],
            null,
            'id',
            true
        );

        $orderStatuses = $orderStatusQuery->find();

        /** @var ModuleQuery $paymentModules : Type 3 = payment */
        $paymentModules = ModuleQuery::create()
            ->findByType(BaseModule::PAYMENT_MODULE_TYPE);
        /** @var ModuleQuery $deliveryModules : Type 3 = delivery */
        $deliveryModules = ModuleQuery::create()
            ->findByType(BaseModule::DELIVERY_MODULE_TYPE);

        $messages = MessageQuery::create()
            ->find();

        $decrementStockConfig = explode(',', StockManager::getConfigValue(StockManager::DECREMENT_STOCK_MODULE_CONFIG_KEY, ""));

        return $this->render(
            "stock-manager/stock-operations",
            compact(
                'decrementStockConfig',
                'stockOperations',
                'orderStatuses',
                'paymentModules',
                'deliveryModules',
                'messages'
            )
        );
    }

    public function addAction()
    {
        if (null !== $response = $this->checkAuth(array(), 'StockManager', AccessManager::UPDATE)) {
            return $response;
        }

        $addForm = $this->createForm('stock_manager_stock_operation');

        try {
            $form = $this->validateForm($addForm, "POST");

            // Get the form field values
            $data = $form->getData();

            $stockOperation = (new StockOperation())
                ->setOperation($data['operation']);

            $stockOperation->save();

            $this->setStockOperationRelations($stockOperation, $data);

            return $this->generateSuccessRedirect($addForm);
        } catch (\Exception $e) {
            $this->setupFormErrorContext(
                $this->getTranslator()->trans("Stock operations"),
                $e->getMessage(),
                $addForm
            );
            return $this->viewAction();
        }
    }

    public function updateAction($id)
    {
        if (null !== $response = $this->checkAuth(array(), 'StockManager', AccessManager::UPDATE)) {
            return $response;
        }

        $updateForm = $this->createForm('stock_manager_stock_operation');

        try {
            $form = $this->validateForm($updateForm, "POST");

            // Get the form field values
            $data = $form->getData();

            $stockOperation = StockOperationQuery::create()
                ->findOneById($id);

            $stockOperation
                ->setOperation($data['operation']);

            $stockOperation->save();

            foreach ($stockOperation->getStockOperationSourceStatuses() as $sourceStatus) {
                $sourceStatus->delete();
            }
            foreach ($stockOperation->getStockOperationTargetStatuses() as $targetStatus) {
                $targetStatus->delete();
            }
            foreach ($stockOperation->getStockOperationPaymentModules() as $paymentModule) {
                $paymentModule->delete();
            }
            foreach ($stockOperation->getStockOperationDeliveryModules() as $deliveryModule) {
                $deliveryModule->delete();
            }

            $this->setStockOperationRelations($stockOperation, $data);

            return $this->generateSuccessRedirect($updateForm);
        } catch (\Exception $e) {
            $this->setupFormErrorContext(
                $this->getTranslator()->trans("Stock operations"),
                $e->getMessage(),
                $updateForm
            );
            return $this->viewAction();
        }
    }

    public function deleteAction()
    {
        if (null !== $response = $this->checkAuth(array(), 'StockManager', AccessManager::UPDATE)) {
            return $response;
        }

        $deleteForm = $this->createForm('stock_manager_stock_operation_delete');

        try {
            $form = $this->validateForm($deleteForm, "POST");

            // Get the form field values
            $data = $form->getData();

            $stockOperation = StockOperationQuery::create()
                ->findOneById($data['stock_operation_id']);

            $stockOperation->delete();

            return $this->generateSuccessRedirect($deleteForm);
        } catch (\Exception $e) {
            $this->setupFormErrorContext(
                $this->getTranslator()->trans("Stock operations"),
                $e->getMessage(),
                $deleteForm
            );
            return $this->viewAction();
        }
    }

    protected function setStockOperationRelations($stockOperation, $data)
    {
        // STATUSES
        $sourcesStatuses = OrderStatusQuery::create()
            ->filterById($data['source_statuses'], Criteria::IN)
            ->find();
        foreach ($sourcesStatuses as $sourcesStatus) {
            (new StockOperationSourceStatus())
                ->setStockOperation($stockOperation)
                ->setOrderStatus($sourcesStatus)
                ->save();
        }
        $targetsStatuses = OrderStatusQuery::create()
            ->filterById($data['target_statuses'], Criteria::IN)
            ->find();
        foreach ($targetsStatuses as $targetsStatus) {
            (new StockOperationTargetStatus())
                ->setStockOperation($stockOperation)
                ->setOrderStatus($targetsStatus)
                ->save();
        }

        // MODULES
        $paymentModules = ModuleQuery::create()
            ->filterById($data['payment_modules'], Criteria::IN)
            ->find();
        foreach ($paymentModules as $paymentModule) {
            (new StockOperationPaymentModule())
                ->setStockOperation($stockOperation)
                ->setModule($paymentModule)
                ->save();
        }
        $deliveryModules = ModuleQuery::create()
            ->filterById($data['delivery_modules'], Criteria::IN)
            ->find();
        foreach ($deliveryModules as $deliveryModule) {
            (new StockOperationDeliveryModule())
                ->setStockOperation($stockOperation)
                ->setModule($deliveryModule)
                ->save();
        }
    }
}
