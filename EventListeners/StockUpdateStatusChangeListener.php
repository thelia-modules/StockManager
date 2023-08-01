<?php

namespace StockManager\EventListeners;

use Propel\Runtime\Exception\PropelException;
use StockManager\Model\StockOperationQuery;
use StockManager\StockManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Action\BaseAction;
use Thelia\Core\Event\Order\GetStockUpdateOperationOnOrderStatusChangeEvent;
use Thelia\Core\Event\Payment\ManageStockOnCreationEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\ModuleQuery;
use Thelia\Module\BaseModule;

class StockUpdateStatusChangeListener extends BaseAction implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        $events = [TheliaEvents::ORDER_GET_STOCK_UPDATE_OPERATION_ON_ORDER_STATUS_CHANGE => [ "getStockOperationOnOrderStatusChange", 256 ]];

        $paymentModules = ModuleQuery::create()
            ->findByType(BaseModule::PAYMENT_MODULE_TYPE);

        foreach ($paymentModules as $paymentModule) {
            $eventName = TheliaEvents::getModuleEvent(
                TheliaEvents::MODULE_PAYMENT_MANAGE_STOCK,
                $paymentModule->getCode()
            );

            $events[$eventName] = ['isStockDecrementOnOrderCreation', 256];
        }

        return $events;
    }

    public function isStockDecrementOnOrderCreation(EventDispatcherInterface $event)
    {
        // Prevent error for Thelia < 2.4
        if (!$event instanceof ManageStockOnCreationEvent) {
            return;
        }

        $paymentModule = $event->getModule();

        $event->setManageStock(
            in_array(
                $paymentModule->getModuleModel()->getId(),
                explode(
                    ',',
                    StockManager::getConfigValue(StockManager::DECREMENT_STOCK_MODULE_CONFIG_KEY, "")
                )
            )
        );
    }

    /**
     * @throws PropelException
     */
    public function getStockOperationOnOrderStatusChange(GetStockUpdateOperationOnOrderStatusChangeEvent $event)
    {
        $event->stopPropagation();

        // The order
        $order = $event->getOrder();

        // The new order status
        $newStatus = $event->getNewOrderStatus();

        if ($newStatus->getId() !== $order->getStatusId()) {
            $stockOperation = StockOperationQuery::create()
                ->useStockOperationSourceStatusQuery()
                    ->filterByOrderStatus($order->getOrderStatus())
                ->endUse()
                ->useStockOperationTargetStatusQuery()
                    ->filterByOrderStatus($newStatus)
                ->endUse()
                ->useStockOperationPaymentModuleQuery()
                    ->filterByPaymentModuleId($order->getPaymentModuleId())
                ->endUse()
                ->useStockOperationDeliveryModuleQuery()
                    ->filterByDeliveryModuleId($order->getDeliveryModuleId())
                ->endUse()
                ->findOne();

            if (null !== $stockOperation) {
                $event->setOperation($stockOperation->getOperation());
            }
        }
    }
}
