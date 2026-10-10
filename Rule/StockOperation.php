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

namespace StockManager\Rule;

use Thelia\Core\Event\Order\GetStockUpdateOperationOnOrderStatusChangeEvent;

enum StockOperation: string
{
    case Decrease = 'decrease';
    case Increase = 'increase';
    case Nothing = 'nothing';

    public static function fromEventOperation(int $operation): self
    {
        return match ($operation) {
            GetStockUpdateOperationOnOrderStatusChangeEvent::DECREASE_STOCK => self::Decrease,
            GetStockUpdateOperationOnOrderStatusChangeEvent::INCREASE_STOCK => self::Increase,
            default => self::Nothing,
        };
    }

    public function toEventOperation(): int
    {
        return match ($this) {
            self::Decrease => GetStockUpdateOperationOnOrderStatusChangeEvent::DECREASE_STOCK,
            self::Increase => GetStockUpdateOperationOnOrderStatusChangeEvent::INCREASE_STOCK,
            self::Nothing => GetStockUpdateOperationOnOrderStatusChangeEvent::DO_NOTHING,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Decrease => 'Take the products out of the stock',
            self::Increase => 'Put the products back in the stock',
            self::Nothing => 'Leave the stock as it is',
        };
    }
}
