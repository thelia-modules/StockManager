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
 * Collects what the rule listener decides while a simulation runs, and tells the
 * listener not to write the decision to the order history then.
 *
 * Shared and mutable on purpose: the simulation dispatches the real decision event, so
 * that it goes through the core and every other listener exactly as a status change does.
 */
final class DecisionRecorder
{
    private bool $simulating = false;
    private ?StockOperation $coreOperation = null;
    private ?StockManagerRule $rule = null;

    public function startSimulation(): void
    {
        $this->simulating = true;
        $this->coreOperation = null;
        $this->rule = null;
    }

    public function stopSimulation(): void
    {
        $this->simulating = false;
        $this->coreOperation = null;
        $this->rule = null;
    }

    public function isSimulating(): bool
    {
        return $this->simulating;
    }

    public function seen(StockOperation $coreOperation, ?StockManagerRule $rule): void
    {
        if (!$this->simulating) {
            return;
        }

        $this->coreOperation = $coreOperation;
        $this->rule = $rule;
    }

    public function coreOperation(): ?StockOperation
    {
        return $this->coreOperation;
    }

    public function rule(): ?StockManagerRule
    {
        return $this->rule;
    }
}
