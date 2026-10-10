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

use StockManager\Model\StockManagerRule;
use StockManager\Model\StockManagerRuleQuery;
use StockManager\Rule\StockOperation;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\ModuleQuery;
use Thelia\Model\Order;
use Thelia\Model\OrderProduct;
use Thelia\Model\OrderStatusQuery;
use Thelia\Model\ProductSaleElements;
use Thelia\Model\ProductSaleElementsQuery;
use Thelia\Test\FixtureFactory;
use Thelia\Test\IntegrationTestCase;

abstract class StockRuleTestCase extends IntegrationTestCase
{
    protected const ORDERED_QUANTITY = 3.0;
    protected const STOCK = 10;

    protected FixtureFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = $this->createFixtureFactory();

        // The rules a shop already has must not decide the tests.
        StockManagerRuleQuery::create()->deleteAll($this->getPropelConnection());
    }

    /**
     * @return array{0: Order, 1: ProductSaleElements}
     */
    protected function orderWithOneLine(string $statusCode): array
    {
        $product = $this->factory->product(
            $this->factory->category(),
            $this->factory->taxRule(),
            $this->factory->currency(),
            ['baseQuantity' => self::STOCK],
        );
        $productSaleElements = ProductSaleElementsQuery::create()
            ->filterByProductId($product->getId())
            ->findOne($this->getPropelConnection());
        self::assertInstanceOf(ProductSaleElements::class, $productSaleElements);

        $order = $this->factory->order($this->factory->customer($this->factory->customerTitle()));
        $order->setStatusId($this->statusId($statusCode))->save($this->getPropelConnection());

        (new OrderProduct())
            ->setOrderId($order->getId())
            ->setProductRef($product->getRef())
            ->setProductSaleElementsRef($productSaleElements->getRef())
            ->setProductSaleElementsId($productSaleElements->getId())
            ->setTitle('Line')
            ->setQuantity(self::ORDERED_QUANTITY)
            ->setPrice('10.000000')
            ->setPromoPrice('10.000000')
            ->setWasNew(0)
            ->setWasInPromo(0)
            ->save($this->getPropelConnection());

        return [$order, $productSaleElements];
    }

    protected function rule(string $from, string $to, StockOperation $operation, ?int $paymentModuleId = null): StockManagerRule
    {
        $rule = (new StockManagerRule())
            ->setPosition(StockManagerRuleQuery::create()->nextPosition())
            ->setSourceStatusId($this->statusId($from))
            ->setTargetStatusId($this->statusId($to))
            ->setOperation($operation->value)
            ->setPaymentRestricted(null !== $paymentModuleId)
            ->setPaymentModuleId($paymentModuleId);
        $rule->save($this->getPropelConnection());

        return $rule;
    }

    protected function changeStatus(Order $order, string $statusCode): void
    {
        $event = new OrderEvent($order);
        $event->setStatus($this->statusId($statusCode));
        $this->dispatcher()->dispatch($event, TheliaEvents::ORDER_UPDATE_STATUS);
    }

    protected function statusId(string $code): int
    {
        $status = OrderStatusQuery::create()->findOneByCode($code, $this->getPropelConnection());
        self::assertNotNull($status, \sprintf('The status "%s" is seeded.', $code));

        return $status->getId();
    }

    protected function stockOf(ProductSaleElements $productSaleElements): float
    {
        return (float) ProductSaleElementsQuery::create()
            ->findPk($productSaleElements->getId(), $this->getPropelConnection())
            ?->getQuantity();
    }

    protected function otherPaymentModuleId(Order $order): int
    {
        $module = ModuleQuery::create()
            ->filterByType(\Thelia\Module\BaseModule::PAYMENT_MODULE_TYPE)
            ->filterById($order->getPaymentModuleId(), \Propel\Runtime\ActiveQuery\Criteria::NOT_EQUAL)
            ->findOne($this->getPropelConnection());
        self::assertNotNull($module, 'A second payment module is installed.');

        return $module->getId();
    }

    protected function dispatcher(): EventDispatcherInterface
    {
        return static::getContainer()->get('event_dispatcher');
    }
}
