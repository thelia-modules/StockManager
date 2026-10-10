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

namespace StockManager\Simulation;

use StockManager\Model\StockManagerRule;
use StockManager\Model\StockManagerRuleQuery;
use StockManager\Rule\DecisionRecorder;
use StockManager\Rule\RuleDecision;
use StockManager\Rule\StockOperation;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Order\GetStockUpdateOperationOnOrderStatusChangeEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\Order;
use Thelia\Model\OrderStatus;

/**
 * Says what a status change would do to the stock, without changing anything.
 */
final readonly class StockSimulator
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private DecisionRecorder $decisionRecorder,
    ) {
    }

    /**
     * For a real order: the decision event is dispatched as the status change would
     * dispatch it, so the core, the rules and any other listener answer exactly as they
     * will. Only the history line is held back.
     */
    public function forOrder(Order $order, OrderStatus $targetStatus): RuleDecision
    {
        $event = new GetStockUpdateOperationOnOrderStatusChangeEvent($order, $targetStatus);

        $this->decisionRecorder->startSimulation();

        try {
            $this->dispatcher->dispatch($event, TheliaEvents::ORDER_GET_STOCK_UPDATE_OPERATION_ON_ORDER_STATUS_CHANGE);

            $operation = StockOperation::fromEventOperation($event->getOperation());

            return new RuleDecision(
                $operation,
                $this->decisionRecorder->coreOperation() ?? $operation,
                $this->decisionRecorder->rule(),
            );
        } finally {
            $this->decisionRecorder->stopSimulation();
        }
    }

    /**
     * For a transition alone, before any order is in it: the rule that would decide, or
     * null when the core would.
     */
    public function ruleFor(int $sourceStatusId, int $targetStatusId, ?int $paymentModuleId): ?StockManagerRule
    {
        return StockManagerRuleQuery::firstApplicable($sourceStatusId, $targetStatusId, $paymentModuleId);
    }
}
