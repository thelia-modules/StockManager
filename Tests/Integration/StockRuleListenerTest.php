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
use StockManager\StockManager;
use Thelia\Model\Order;
use Thelia\Model\OrderHistory;
use Thelia\Model\OrderHistoryQuery;
use Thelia\Model\OrderStatus;
use Thelia\Model\OrderStatusQuery;

final class StockRuleListenerTest extends StockRuleTestCase
{
    public function testWithoutAnyRuleTheCoreStillTakesTheStockOfAPaidOrderAndGivesBackARefundedOne(): void
    {
        [$order, $productSaleElements] = $this->orderWithOneLine('not_paid');

        $this->changeStatus($order, 'paid');
        self::assertSame(self::STOCK - self::ORDERED_QUANTITY, $this->stockOf($productSaleElements));

        $this->changeStatus($order, 'not_paid');
        self::assertSame((float) self::STOCK, $this->stockOf($productSaleElements));
        self::assertSame([], $this->ruleLines($order));
    }

    public function testARuleThatLeavesTheStockAloneKeepsItWhenAPaidOrderIsSent(): void
    {
        [$order, $productSaleElements] = $this->orderWithOneLine('paid');
        $this->rule('paid', 'sent', StockOperation::Nothing);

        $this->changeStatus($order, 'sent');

        self::assertSame((float) self::STOCK, $this->stockOf($productSaleElements));
    }

    public function testARuleThatGivesTheStockBackOnATransitionTheCoreIgnoresGivesItBack(): void
    {
        [$order, $productSaleElements] = $this->orderWithOneLine('paid');
        $this->rule('paid', 'processing', StockOperation::Increase);

        $this->changeStatus($order, 'processing');

        self::assertSame(self::STOCK + self::ORDERED_QUANTITY, $this->stockOf($productSaleElements));
    }

    public function testARuleThatOverridesTheCoreStopsAPaymentFromTakingTheStock(): void
    {
        [$order, $productSaleElements] = $this->orderWithOneLine('not_paid');
        $this->rule('not_paid', 'paid', StockOperation::Nothing);

        $this->changeStatus($order, 'paid');

        self::assertSame((float) self::STOCK, $this->stockOf($productSaleElements));
        self::assertSame('paid', OrderStatusQuery::create()->findPk($this->reread($order)->getStatusId(), $this->getPropelConnection())?->getCode());
    }

    public function testARuleRestrictedToAPaymentModuleOnlyAppliesToTheOrdersPaidWithIt(): void
    {
        [$order, $productSaleElements] = $this->orderWithOneLine('paid');
        $this->rule('paid', 'processing', StockOperation::Increase, $this->otherPaymentModuleId($order));

        $this->changeStatus($order, 'processing');
        self::assertSame((float) self::STOCK, $this->stockOf($productSaleElements), 'The rule names another payment module.');

        [$secondOrder, $secondProductSaleElements] = $this->orderWithOneLine('paid');
        $this->rule('paid', 'processing', StockOperation::Increase, (int) $secondOrder->getPaymentModuleId());

        $this->changeStatus($secondOrder, 'processing');
        self::assertSame(self::STOCK + self::ORDERED_QUANTITY, $this->stockOf($secondProductSaleElements));
    }

    public function testTheFirstRuleInTheOrderWins(): void
    {
        [$order, $productSaleElements] = $this->orderWithOneLine('paid');
        $first = $this->rule('paid', 'processing', StockOperation::Nothing);
        $this->rule('paid', 'processing', StockOperation::Increase);

        $this->changeStatus($order, 'processing');

        self::assertSame((float) self::STOCK, $this->stockOf($productSaleElements));
        self::assertSame($first->getId(), $this->ruleLines($order)[0]['rule_id']);
    }

    public function testARuleWhoseStatusWasDeletedIsIgnored(): void
    {
        [$order, $productSaleElements] = $this->orderWithOneLine('paid');
        $custom = (new OrderStatus())
            ->setCode('stock_manager_test_'.uniqid())
            ->setColor('#000000')
            ->setPosition(99);
        $custom->save($this->getPropelConnection());

        $broken = $this->rule('paid', 'processing', StockOperation::Increase);
        $broken->setTargetStatusId($custom->getId())->save($this->getPropelConnection());
        $custom->delete($this->getPropelConnection());
        $broken->reload(false, $this->getPropelConnection());

        self::assertNull($broken->getTargetStatusId(), 'Deleting the status kept the rule, without it.');
        self::assertNotNull($broken->getBrokenReason());

        $this->changeStatus($order, 'processing');

        self::assertSame((float) self::STOCK, $this->stockOf($productSaleElements));
    }

    public function testARuleWhosePaymentModuleWasDeletedIsIgnored(): void
    {
        [$order, $productSaleElements] = $this->orderWithOneLine('paid');
        $broken = $this->rule('paid', 'processing', StockOperation::Increase, (int) $order->getPaymentModuleId());
        // What the foreign key leaves when the module row is deleted.
        $broken->setPaymentModuleId(null)->save($this->getPropelConnection());

        self::assertSame('Its payment module was deleted.', $broken->getBrokenReason());

        $this->changeStatus($order, 'processing');

        self::assertSame((float) self::STOCK, $this->stockOf($productSaleElements), 'A restriction that lost its module does not widen to every order.');
    }

    public function testTheOrderHistorySaysWhichRuleDecided(): void
    {
        [$order] = $this->orderWithOneLine('paid');
        $rule = $this->rule('paid', 'sent', StockOperation::Nothing);

        $this->changeStatus($order, 'sent');

        $lines = OrderHistoryQuery::create()
            ->filterByOrderId($order->getId())
            ->filterByEventType(StockManager::HISTORY_EVENT_TYPE)
            ->find($this->getPropelConnection());
        self::assertCount(1, $lines);

        /** @var OrderHistory $line */
        $line = $lines->getFirst();
        $payload = json_decode((string) $line->getPayload(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame($rule->getId(), $payload['rule_id']);
        self::assertSame('paid', $payload['from_status']);
        self::assertSame('sent', $payload['to_status']);
        self::assertSame('nothing', $payload['operation']);
        self::assertSame('nothing', $payload['core_operation']);
        self::assertSame('module', $line->getActorType(), 'No admin in session: the change came from elsewhere than a browser.');
        self::assertSame('StockManager', $line->getActorLabel());
        self::assertSame(
            \sprintf('Stock rule %d: Leave the stock as it is (without the rule: Leave the stock as it is).', $rule->getId()),
            $line->getComment(),
        );
    }

    public function testATransitionMadeTwiceIsWrittenTwice(): void
    {
        [$order] = $this->orderWithOneLine('paid');
        $this->rule('paid', 'processing', StockOperation::Nothing);

        // No rule on the way back: the two lines of the rule follow each other with the
        // same transition, which the recorder would take for a duplicate.
        $this->changeStatus($order, 'processing');
        $this->changeStatus($order, 'paid');
        $this->changeStatus($order, 'processing');

        self::assertCount(2, $this->ruleLines($order));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function ruleLines(Order $order): array
    {
        $lines = [];
        foreach (OrderHistoryQuery::create()
            ->filterByOrderId($order->getId())
            ->filterByEventType(StockManager::HISTORY_EVENT_TYPE)
            ->orderById()
            ->find($this->getPropelConnection()) as $line) {
            $lines[] = json_decode((string) $line->getPayload(), true, flags: \JSON_THROW_ON_ERROR);
        }

        return $lines;
    }

    private function reread(Order $order): Order
    {
        $order->reload(false, $this->getPropelConnection());

        return $order;
    }
}
