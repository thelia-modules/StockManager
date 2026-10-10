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

namespace StockManager\Tests\Integration;

use StockManager\Rule\StockOperation;
use StockManager\Simulation\StockSimulator;
use StockManager\StockManager;
use Thelia\Model\OrderHistoryQuery;
use Thelia\Model\OrderStatusQuery;

final class StockSimulatorTest extends StockRuleTestCase
{
    public function testTheSimulationOfAnOrderAnnouncesWhatTheStatusChangeThenDoes(): void
    {
        [$order, $productSaleElements] = $this->orderWithOneLine('paid');
        $rule = $this->rule('paid', 'processing', StockOperation::Increase);

        $decision = $this->simulator()->forOrder($order, $this->orderStatus('processing'));

        self::assertSame(StockOperation::Increase, $decision->operation);
        self::assertSame(StockOperation::Nothing, $decision->coreOperation);
        self::assertSame($rule->getId(), $decision->rule?->getId());
        self::assertSame((float) self::STOCK, $this->stockOf($productSaleElements), 'A simulation moves no stock.');
        self::assertSame(0, OrderHistoryQuery::create()->filterByOrderId($order->getId())->filterByEventType(StockManager::HISTORY_EVENT_TYPE)->count($this->getPropelConnection()), 'A simulation writes no history.');

        $this->changeStatus($order, 'processing');

        self::assertSame(self::STOCK + self::ORDERED_QUANTITY, $this->stockOf($productSaleElements));
        self::assertSame(1, OrderHistoryQuery::create()->filterByOrderId($order->getId())->filterByEventType(StockManager::HISTORY_EVENT_TYPE)->count($this->getPropelConnection()));
    }

    public function testWithoutARuleTheSimulationGivesTheDecisionOfTheCore(): void
    {
        [$order, $productSaleElements] = $this->orderWithOneLine('not_paid');

        $decision = $this->simulator()->forOrder($order, $this->orderStatus('paid'));

        self::assertNull($decision->rule);
        self::assertSame(StockOperation::Decrease, $decision->operation);
        self::assertSame(StockOperation::Decrease, $decision->coreOperation);

        $this->changeStatus($order, 'paid');
        self::assertSame(self::STOCK - self::ORDERED_QUANTITY, $this->stockOf($productSaleElements));
    }

    public function testATransitionAloneNamesTheRuleThatWouldDecide(): void
    {
        [$order] = $this->orderWithOneLine('paid');
        $restricted = $this->rule('paid', 'sent', StockOperation::Increase, $this->otherPaymentModuleId($order));
        $open = $this->rule('paid', 'sent', StockOperation::Nothing);

        self::assertSame($restricted->getId(), $this->simulator()->ruleFor($this->statusId('paid'), $this->statusId('sent'), $restricted->getPaymentModuleId())?->getId());
        self::assertSame($open->getId(), $this->simulator()->ruleFor($this->statusId('paid'), $this->statusId('sent'), null)?->getId());
        self::assertNull($this->simulator()->ruleFor($this->statusId('sent'), $this->statusId('paid'), null));
    }

    private function orderStatus(string $code): \Thelia\Model\OrderStatus
    {
        $status = OrderStatusQuery::create()->findOneByCode($code, $this->getPropelConnection());
        self::assertNotNull($status);

        return $status;
    }

    private function simulator(): StockSimulator
    {
        return static::getContainer()->get(StockSimulator::class);
    }
}
