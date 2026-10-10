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

namespace StockManager\Model;

use StockManager\Model\Base\StockManagerRule as BaseStockManagerRule;
use StockManager\Rule\StockOperation;

class StockManagerRule extends BaseStockManagerRule
{
    public function getStockOperation(): ?StockOperation
    {
        return StockOperation::tryFrom((string) $this->getOperation());
    }

    /**
     * Why the rule can no longer match, or null when it can: a status or a payment
     * module it names was deleted since it was written.
     */
    public function getBrokenReason(): ?string
    {
        return match (true) {
            null === $this->getSourceStatusId() => 'Its starting status was deleted.',
            null === $this->getTargetStatusId() => 'Its target status was deleted.',
            $this->getPaymentRestricted() && null === $this->getPaymentModuleId() => 'Its payment module was deleted.',
            null === $this->getStockOperation() => 'Its operation is unknown.',
            default => null,
        };
    }
}
