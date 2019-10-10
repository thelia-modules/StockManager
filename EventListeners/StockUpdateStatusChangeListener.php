<?php

namespace StockManager\EventListeners;

use StockManager\Model\StockOperationQuery;
use StockManager\StockManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Action\BaseAction;
use Thelia\Core\Event\Order\GetStockUpdateOperationOnOrderStatusChangeEvent;
use Thelia\Core\Event\Payment\ManageStockOnCreationEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\ModuleQuery;

class StockUpdateStatusChangeListener extends BaseAction implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $events = [TheliaEvents::ORDER_GET_STOCK_UPDATE_OPERATION_ON_ORDER_STATUS_CHANGE => [ "getStockOperationOnOrderStatusChange", 256 ]];

        $paymentModules = ModuleQuery::create()
            ->findByCategory('payment');

        foreach ($paymentModules as $paymentModule) {
            $eventName = TheliaEvents::getModuleEvent(
                TheliaEvents::MODULE_PAYMENT_MANAGE_STOCK,
                $paymentModule->getCode()
            );

            $events[$eventName] = ['isStockDecrementOnOrderCreation', 256];
        }

        return $events;
    }

    public function isStockDecrementOnOrderCreation(ManageStockOnCreationEvent $event)
    {
        $paymentModule = $event->getModule();

        $event->setManageStock(
            in_array(
                $paymentModule->getModuleModel()->getId(),
                explode(
                    ',',
                    StockManager::getConfigValue(StockManager::DECREMENT_STOCK_MODULE_CONFIG_KEY, [])
                )
            )
        );
    }

    public function getStockOperationOnOrderStatusChange(GetStockUpdateOperationOnOrderStatusChangeEvent $event, $eventName, EventDispatcherInterface $dispatcher)
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