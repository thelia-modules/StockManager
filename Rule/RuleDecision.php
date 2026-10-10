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

use StockManager\Model\StockManagerRule;

/**
 * What a status change does to the stock: the operation applied, the rule that decided
 * it (null when the core did), and what the core alone would have done.
 */
final readonly class RuleDecision
{
    public function __construct(
        public StockOperation $operation,
        public StockOperation $coreOperation,
        public ?StockManagerRule $rule,
    ) {
    }
}
