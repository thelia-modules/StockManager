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

use Propel\Runtime\ActiveQuery\Criteria;
use StockManager\Model\Base\StockManagerRuleQuery as BaseStockManagerRuleQuery;
use StockManager\Model\Map\StockManagerRuleTableMap;

class StockManagerRuleQuery extends BaseStockManagerRuleQuery
{
    /**
     * The rules in the order they are evaluated.
     */
    public function inEvaluationOrder(): self
    {
        return $this->orderByPosition(Criteria::ASC)->orderById(Criteria::ASC);
    }

    /**
     * The rules that match a transition, first match first. A rule whose status or
     * payment module was deleted holds NULL there and never matches.
     */
    public function matching(int $sourceStatusId, int $targetStatusId, ?int $paymentModuleId): self
    {
        $this
            ->filterBySourceStatusId($sourceStatusId)
            ->filterByTargetStatusId($targetStatusId)
            ->condition('unrestricted', StockManagerRuleTableMap::COL_PAYMENT_RESTRICTED.' = ?', false)
            ->condition('samePayment', StockManagerRuleTableMap::COL_PAYMENT_MODULE_ID.' = ?', $paymentModuleId ?? 0)
            ->where(['unrestricted', 'samePayment'], Criteria::LOGICAL_OR);

        return $this->inEvaluationOrder();
    }

    /**
     * The rule that decides a transition: the first match whose operation is known.
     */
    public static function firstApplicable(int $sourceStatusId, int $targetStatusId, ?int $paymentModuleId): ?StockManagerRule
    {
        foreach (self::create()->matching($sourceStatusId, $targetStatusId, $paymentModuleId)->find() as $rule) {
            if (null !== $rule->getStockOperation()) {
                return $rule;
            }
        }

        return null;
    }

    public function nextPosition(): int
    {
        $last = self::create()->orderByPosition(Criteria::DESC)->findOne();

        return null === $last ? 1 : $last->getPosition() + 1;
    }
}
