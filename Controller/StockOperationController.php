<?php

namespace StockManager\Controller;

use Exception;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Exception\PropelException;
use StockManager\Form\StockOperationDeleteForm;
use StockManager\Form\StockOperationForm;
use StockManager\Model\StockOperation;
use StockManager\Model\StockOperationDeliveryModule;
use StockManager\Model\StockOperationPaymentModule;
use StockManager\Model\StockOperationQuery;
use StockManager\Model\StockOperationSourceStatus;
use StockManager\Model\StockOperationTargetStatus;
use StockManager\StockManager;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Translation\Translator;
use Thelia\Model\MessageQuery;
use Thelia\Model\ModuleQuery;
use Thelia\Model\OrderStatusQuery;
use Thelia\Model\Tools\ModelCriteriaTools;
use Thelia\Module\BaseModule;

#[Route('/admin/module/StockManager', name: 'stock_manager_stock_operation')]
class StockOperationController extends BaseAdminController
{
    #[Route('/StockOperation', name: 'view', methods: 'GET')]
    public function viewAction(Session $session): Response
    {
        if (null !== $response = $this->checkAuth(array(), 'StockManager', AccessManager::VIEW)) {
            return $response;
        }

        $stockOperations = StockOperationQuery::create()
            ->find();

        $orderStatusQuery = OrderStatusQuery::create();

        ModelCriteriaTools::getI18n(
            true,
            $session->getLang()->getId(),
            $orderStatusQuery,
            $session->getLang()->getLocale(),
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

    #[Route('/StockOperation', name: 'add', methods: 'POST')]
    public function addAction(Session $session)
    {
        if (null !== $response = $this->checkAuth(array(), 'StockManager', AccessManager::UPDATE)) {
            return $response;
        }

        $addForm = $this->createForm(StockOperationForm::getName());

        try {
            $form = $this->validateForm($addForm);

            // Get the form field values
            $data = $form->getData();

            $stockOperation = (new StockOperation())
                ->setOperation($data['operation']);

            $stockOperation->save();

            $this->setStockOperationRelations($stockOperation, $data);

            return $this->generateSuccessRedirect($addForm);
        } catch (Exception $e) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans("Stock operations"),
                $e->getMessage(),
                $addForm
            );
            return $this->viewAction($session);
        }
    }

    #[Route('/StockOperation/update/{id}', name: 'update', methods: 'POST')]
    public function updateAction(Session $session, $id)
    {
        if (null !== $response = $this->checkAuth(array(), 'StockManager', AccessManager::UPDATE)) {
            return $response;
        }

        $updateForm = $this->createForm(StockOperationForm::getName());

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
        } catch (Exception $e) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans("Stock operations"),
                $e->getMessage(),
                $updateForm
            );
            return $this->viewAction($session);
        }
    }

    #[Route('/StockOperation/delete', name: 'delete', methods: 'POST')]
    public function deleteAction(Session $session)
    {
        if (null !== $response = $this->checkAuth(array(), 'StockManager', AccessManager::UPDATE)) {
            return $response;
        }

        $deleteForm = $this->createForm(StockOperationDeleteForm::getName());

        try {
            $form = $this->validateForm($deleteForm, "POST");

            // Get the form field values
            $data = $form->getData();

            $stockOperation = StockOperationQuery::create()
                ->findOneById($data['stock_operation_id']);

            $stockOperation->delete();

            return $this->generateSuccessRedirect($deleteForm);
        } catch (Exception $e) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans("Stock operations"),
                $e->getMessage(),
                $deleteForm
            );
            return $this->viewAction($session);
        }
    }

    /**
     * @throws PropelException
     */
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
