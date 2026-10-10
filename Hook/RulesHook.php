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

namespace StockManager\Hook;

use StockManager\Model\StockManagerRule;
use StockManager\Model\StockManagerRuleQuery;
use StockManager\Rule\StockOperation;
use StockManager\Simulation\StockSimulator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Core\Hook\BaseHook;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Security\SecurityContext;
use Thelia\Core\Template\Parser\ParserResolver;
use Thelia\Domain\Order\Service\OrderStatusTransitionGuard;
use Thelia\Model\Module;
use Thelia\Model\ModuleQuery;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatus;
use Thelia\Model\OrderStatusQuery;
use Thelia\Module\BaseModule;

/**
 * The configuration page of the module: the rules in the order they are evaluated, the
 * form to add one, and the simulation, which reads its question from the query string so
 * that asking it changes nothing.
 */
class RulesHook extends BaseHook
{
    public function __construct(
        private readonly StockSimulator $simulator,
        private readonly OrderStatusTransitionGuard $transitionGuard,
        private readonly SecurityContext $securityContext,
        private readonly RequestStack $requestStack,
        ?EventDispatcherInterface $dispatcher = null,
        ?ParserResolver $parserResolver = null,
    ) {
        parent::__construct($dispatcher, $parserResolver);
    }

    /**
     * @return array<string, list<array{type: string, method: string}>>
     */
    public static function getSubscribedHooks(): array
    {
        return [
            'module.configuration' => [
                ['type' => 'back', 'method' => 'onModuleConfiguration'],
            ],
        ];
    }

    /**
     * The request comes from the stack, not from BaseHook::getRequest(), which keeps the
     * first request the service saw and would answer a later one with its query string.
     */
    public function onModuleConfiguration(HookRenderEvent $event): void
    {
        $session = $this->requestStack->getCurrentRequest()?->getSession();
        $locale = ($session instanceof Session ? $session->getLang()?->getLocale() : null)
            ?? $this->requestStack->getCurrentRequest()?->getLocale()
            ?? 'en_US';

        $statuses = [];
        foreach (OrderStatusQuery::create()->orderByPosition()->find() as $status) {
            $statuses[$status->getId()] = $this->statusTitle($status, $locale);
        }

        // Every payment module names the rules written for it; only the active ones are
        // offered to a new rule and to the simulation.
        $paymentModules = [];
        $activePaymentModules = [];
        foreach (ModuleQuery::create()->filterByType(BaseModule::PAYMENT_MODULE_TYPE)->orderByCode()->find() as $module) {
            $paymentModules[$module->getId()] = $this->moduleTitle($module, $locale);
            if ($module->getActivate()) {
                $activePaymentModules[$module->getId()] = $paymentModules[$module->getId()];
            }
        }

        $rules = [];
        foreach (StockManagerRuleQuery::create()->inEvaluationOrder()->find() as $rule) {
            $rules[] = $this->ruleView($rule, \count($rules) + 1, $statuses, $paymentModules);
        }
        $ranks = array_column($rules, 'rank', 'id');

        $event->add($this->render('StockManager/rules.html.twig', [
            'rules' => $rules,
            'statuses' => $statuses,
            'payment_modules' => $activePaymentModules,
            'operations' => array_combine(
                array_map(static fn (StockOperation $operation) => $operation->value, StockOperation::cases()),
                array_map(static fn (StockOperation $operation) => $operation->label(), StockOperation::cases()),
            ),
            'simulation' => $this->simulation($statuses, $paymentModules, $ranks),
            // What the simulation was asked, so that its form shows it again.
            'simulation_input' => [
                'source' => (int) $this->requestStack->getCurrentRequest()?->query->get('simulate_source'),
                'target' => (int) $this->requestStack->getCurrentRequest()?->query->get('simulate_target'),
                'payment' => (int) $this->requestStack->getCurrentRequest()?->query->get('simulate_payment'),
            ],
        ]));
    }

    /**
     * @param array<int, string> $statuses
     * @param array<int, string> $paymentModules
     *
     * @return array<string, mixed>
     */
    private function ruleView(StockManagerRule $rule, int $rank, array $statuses, array $paymentModules): array
    {
        return [
            'id' => $rule->getId(),
            'rank' => $rank,
            'source' => self::titleOf($statuses, $rule->getSourceStatusId()),
            'target' => self::titleOf($statuses, $rule->getTargetStatusId()),
            'operation' => $rule->getStockOperation()?->label(),
            'payment_module' => $rule->getPaymentRestricted() ? self::titleOf($paymentModules, $rule->getPaymentModuleId()) : null,
            'broken' => $rule->getBrokenReason(),
        ];
    }

    /**
     * @param array<int, string> $statuses
     * @param array<int, string> $paymentModules
     * @param array<int, int>    $ranks          the place of each rule in the list, by id
     *
     * @return array<string, mixed>|null
     */
    private function simulation(array $statuses, array $paymentModules, array $ranks): ?array
    {
        $query = $this->requestStack->getCurrentRequest()?->query;
        $targetStatusId = (int) $query?->get('simulate_target');

        if (0 === $targetStatusId) {
            return null;
        }

        $targetStatus = OrderStatusQuery::create()->findPk($targetStatusId);
        if (null === $targetStatus) {
            return ['error' => 'Choose a target status.'];
        }

        $orderReference = trim((string) $query->get('simulate_order'));

        // Reading an order through the simulation needs the right to read orders.
        if ('' !== $orderReference && !$this->securityContext->isGranted(['ADMIN'], [AdminResources::ORDER], [], [AccessManager::VIEW])) {
            return ['error' => 'Reading an order needs the right to view orders.'];
        }

        if ('' !== $orderReference) {
            $order = OrderQuery::create()->findOneByRef($orderReference);
            if (null === $order) {
                return ['error' => 'No order has this reference.', 'order_reference' => $orderReference];
            }

            $decision = $this->simulator->forOrder($order, $targetStatus);

            return [
                'order_reference' => $order->getRef(),
                'source' => self::titleOf($statuses, $order->getStatusId()),
                'target' => $statuses[$targetStatusId],
                'payment_module' => self::titleOf($paymentModules, $order->getPaymentModuleId()),
                'same_status' => $order->getStatusId() === $targetStatusId,
                'transition_allowed' => $this->transitionGuard->isAllowed((int) $order->getStatusId(), $targetStatusId),
                'operation' => $decision->operation->label(),
                'core_operation' => $decision->coreOperation->label(),
                'rule' => null === $decision->rule ? null : $this->ruleView($decision->rule, $ranks[$decision->rule->getId()] ?? 0, $statuses, $paymentModules),
            ];
        }

        $sourceStatusId = (int) $query->get('simulate_source');
        if (!isset($statuses[$sourceStatusId])) {
            return ['error' => 'Choose an order, or a starting status.'];
        }

        $paymentModuleId = (int) $query->get('simulate_payment');
        $rule = $this->simulator->ruleFor($sourceStatusId, $targetStatusId, 0 !== $paymentModuleId ? $paymentModuleId : null);

        return [
            'order_reference' => null,
            'source' => $statuses[$sourceStatusId],
            'target' => $statuses[$targetStatusId],
            'payment_module' => $paymentModules[$paymentModuleId] ?? null,
            'same_status' => $sourceStatusId === $targetStatusId,
            'transition_allowed' => $this->transitionGuard->isAllowed($sourceStatusId, $targetStatusId),
            'operation' => $rule?->getStockOperation()?->label(),
            'core_operation' => null,
            'rule' => null === $rule ? null : $this->ruleView($rule, $ranks[$rule->getId()] ?? 0, $statuses, $paymentModules),
        ];
    }

    /**
     * @param array<int, string> $titles
     */
    private static function titleOf(array $titles, ?int $id): ?string
    {
        return null === $id ? null : ($titles[$id] ?? null);
    }

    private function statusTitle(OrderStatus $status, string $locale): string
    {
        $title = $status->setLocale($locale)->getTitle();

        return null === $title || '' === $title ? (string) $status->getCode() : $title;
    }

    private function moduleTitle(Module $module, string $locale): string
    {
        $title = $module->setLocale($locale)->getTitle();

        return null === $title || '' === $title ? (string) $module->getCode() : $title;
    }
}
