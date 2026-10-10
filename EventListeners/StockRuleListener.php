<?php

declare(strict_types=1);

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StockManager\EventListeners;

use StockManager\Model\StockManagerRuleQuery;
use StockManager\Rule\DecisionRecorder;
use StockManager\Rule\StockOperation;
use StockManager\StockManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Thelia\Core\Event\Order\GetStockUpdateOperationOnOrderStatusChangeEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Domain\Order\Service\OrderHistoryRecorder;
use Thelia\Model\OrderHistoryQuery;

/**
 * Listens after the core (priority 128): the core decides first, a rule that matches the
 * transition replaces its decision, and without a rule the core decision stands.
 *
 * The event is dispatched inside the transaction of the status change, so the history
 * line goes away with the stock movement and the status when the change fails.
 */
final readonly class StockRuleListener implements EventSubscriberInterface
{
    public const PRIORITY = 64;

    public function __construct(
        private OrderHistoryRecorder $historyRecorder,
        private DecisionRecorder $decisionRecorder,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TheliaEvents::ORDER_GET_STOCK_UPDATE_OPERATION_ON_ORDER_STATUS_CHANGE => ['applyRule', self::PRIORITY],
        ];
    }

    public function applyRule(GetStockUpdateOperationOnOrderStatusChangeEvent $event): void
    {
        $order = $event->getOrder();
        $targetStatus = $event->getNewOrderStatus();
        $coreOperation = StockOperation::fromEventOperation($event->getOperation());

        if ($targetStatus->getId() === $order->getStatusId()) {
            $this->decisionRecorder->seen($coreOperation, null);

            return;
        }

        $rule = StockManagerRuleQuery::firstApplicable((int) $order->getStatusId(), (int) $targetStatus->getId(), $order->getPaymentModuleId());
        $operation = $rule?->getStockOperation();

        if (null === $rule || null === $operation) {
            $this->decisionRecorder->seen($coreOperation, null);

            return;
        }

        $event->setOperation($operation->toEventOperation());
        $this->decisionRecorder->seen($coreOperation, $rule);

        if ($this->decisionRecorder->isSimulating()) {
            return;
        }

        $this->historyRecorder->record(
            (int) $order->getId(),
            StockManager::HISTORY_EVENT_TYPE,
            [
                'rule_id' => $rule->getId(),
                'from_status' => $order->getOrderStatus()->getCode(),
                'to_status' => $targetStatus->getCode(),
                'operation' => $operation->value,
                'core_operation' => $coreOperation->value,
                // Two identical lines in a row are deduplicated by the recorder: the
                // sequence tells apart a transition made twice.
                'sequence' => OrderHistoryQuery::create()
                    ->filterByOrderId($order->getId())
                    ->filterByEventType(StockManager::HISTORY_EVENT_TYPE)
                    ->count() + 1,
            ],
            // The back office shows a module's line with its raw type: the comment is
            // what the merchant reads on the order.
            comment: $this->translator->trans(
                'Stock rule %id%: %operation% (without the rule: %core_operation%).',
                [
                    '%id%' => $rule->getId(),
                    '%operation%' => $this->translator->trans($operation->label(), [], StockManager::DOMAIN_NAME),
                    '%core_operation%' => $this->translator->trans($coreOperation->label(), [], StockManager::DOMAIN_NAME),
                ],
                StockManager::DOMAIN_NAME,
            ),
            moduleCode: StockManager::getModuleCode(),
        );
    }
}
