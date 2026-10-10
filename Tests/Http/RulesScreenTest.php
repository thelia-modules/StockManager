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

namespace StockManager\Tests\Http;

use StockManager\Model\StockManagerRule;
use StockManager\Model\StockManagerRuleQuery;
use StockManager\Rule\StockOperation;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Model\Admin;
use Thelia\Model\ModuleQuery;
use Thelia\Model\OrderStatusQuery;
use Thelia\Model\ProfileModule;
use Thelia\Test\FixtureFactory;
use Thelia\Test\WebIntegrationTestCase;
use Thelia\Tests\Support\BackOffice\AdminSessionInjector;

/**
 * The configuration page of the module: the rules in their order, adding, moving and
 * deleting one, and the simulation.
 */
final class RulesScreenTest extends WebIntegrationTestCase
{
    private const CONFIGURATION_URL = '/admin/module/StockManager';

    private ?AdminSessionInjector $injector = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->injector = new AdminSessionInjector();
        $this->getService(EventDispatcherInterface::class)->addSubscriber($this->injector);

        StockManagerRuleQuery::create()->deleteAll();
        $this->loginAs($this->factory()->admin());
    }

    protected function tearDown(): void
    {
        $this->injector?->clear();

        parent::tearDown();
    }

    public function testThePageListsTheRulesInTheOrderTheyAreEvaluated(): void
    {
        $second = $this->rule('paid', 'sent', StockOperation::Nothing, position: 2);
        $first = $this->rule('paid', 'processing', StockOperation::Increase, position: 1);

        $this->assertPageRenders(self::CONFIGURATION_URL);

        $rows = $this->client->getCrawler()->filter('[data-testid="stockmanager-rule-list"] tbody tr');
        self::assertSame(
            ['stockmanager-rule-'.$first->getId(), 'stockmanager-rule-'.$second->getId()],
            $rows->each(static fn ($row): string => (string) $row->attr('data-testid')),
        );
    }

    public function testARuleAddedFromTheFormGoesAtTheEndOfTheList(): void
    {
        $existing = $this->rule('paid', 'processing', StockOperation::Increase, position: 1);
        $this->assertPageRenders(self::CONFIGURATION_URL);

        $form = $this->client->getCrawler()->filter('[data-testid="stockmanager-rule-form"]')->form();
        $this->client->submit($form, [
            'source_status_id' => (string) $this->statusId('paid'),
            'target_status_id' => (string) $this->statusId('sent'),
            'operation' => 'nothing',
            'payment_module_id' => '0',
        ]);

        self::assertTrue($this->client->getResponse()->isRedirect());
        $rules = StockManagerRuleQuery::create()->inEvaluationOrder()->find();
        self::assertCount(2, $rules);
        self::assertSame($existing->getId(), $rules[0]->getId());
        self::assertSame($this->statusId('sent'), $rules[1]->getTargetStatusId());
        self::assertSame('nothing', $rules[1]->getOperation());
        self::assertFalse($rules[1]->getPaymentRestricted());
    }

    public function testARuleFromAStatusToItselfIsRefused(): void
    {
        $this->assertPageRenders(self::CONFIGURATION_URL);

        $form = $this->client->getCrawler()->filter('[data-testid="stockmanager-rule-form"]')->form();
        $this->client->submit($form, [
            'source_status_id' => (string) $this->statusId('paid'),
            'target_status_id' => (string) $this->statusId('paid'),
            'operation' => 'nothing',
        ]);

        self::assertTrue($this->client->getResponse()->isRedirect(), 'The administrator is sent back to the rules.');
        self::assertSame(0, StockManagerRuleQuery::create()->count());
        $this->client->followRedirect();
        self::assertStringContainsString(
            'The starting status and the target status must differ.',
            $this->client->getCrawler()->filter('[data-testid="bo-flash-danger"]')->text(),
        );
    }

    public function testAPostWithoutTheTokenAddsNothing(): void
    {
        $this->client->request('POST', '/admin/modules/StockManager/rules', [
            'source_status_id' => (string) $this->statusId('paid'),
            'target_status_id' => (string) $this->statusId('sent'),
            'operation' => 'nothing',
        ]);

        self::assertFalse($this->client->getResponse()->isRedirect(), 'The request is refused, not handled.');
        self::assertSame(0, StockManagerRuleQuery::create()->count());
    }

    public function testMovingOrDeletingWithoutTheTokenChangesNothing(): void
    {
        $first = $this->rule('paid', 'processing', StockOperation::Increase, position: 1);
        $second = $this->rule('paid', 'sent', StockOperation::Nothing, position: 2);

        $this->client->request('POST', '/admin/modules/StockManager/rules/'.$second->getId().'/move', ['direction' => 'up']);
        self::assertFalse($this->client->getResponse()->isRedirect());
        $this->client->request('POST', '/admin/modules/StockManager/rules/'.$first->getId().'/delete');
        self::assertFalse($this->client->getResponse()->isRedirect());

        self::assertSame(
            [$first->getId(), $second->getId()],
            array_map(static fn (StockManagerRule $rule): int => $rule->getId(), iterator_to_array(StockManagerRuleQuery::create()->inEvaluationOrder()->find())),
        );
    }

    public function testAnAdministratorWithoutTheRightToTheModuleChangesNoRule(): void
    {
        $rule = $this->rule('paid', 'processing', StockOperation::Increase, position: 1);
        $this->assertPageRenders(self::CONFIGURATION_URL);
        $forms = $this->client->getCrawler();
        $create = $forms->filter('[data-testid="stockmanager-rule-form"]')->form();
        $delete = $forms->filter('[data-testid="stockmanager-rule-'.$rule->getId().'"] form')
            ->reduce(static fn ($node): bool => str_contains((string) $node->attr('action'), '/delete'))
            ->form();

        // Same session, same token: only the profile of the administrator changes.
        $this->loginAs($this->factory()->restrictedAdmin([AdminResources::ORDER => [AccessManager::VIEW]]));

        $this->client->submit($create, ['source_status_id' => (string) $this->statusId('paid'), 'target_status_id' => (string) $this->statusId('sent'), 'operation' => 'nothing']);
        self::assertSame(403, $this->client->getResponse()->getStatusCode());
        $this->client->submit($delete);
        self::assertSame(403, $this->client->getResponse()->getStatusCode());
        $this->client->request('POST', '/admin/modules/StockManager/rules/'.$rule->getId().'/move', ['direction' => 'down']);
        self::assertSame(403, $this->client->getResponse()->getStatusCode());

        self::assertSame(1, StockManagerRuleQuery::create()->count());
    }

    public function testSimulatingAnOrderNeedsTheRightToViewOrders(): void
    {
        $order = $this->factory()->order();
        $admin = $this->factory()->restrictedAdmin([AdminResources::MODULE => [AccessManager::VIEW]]);
        $this->grantTheModule($admin, [AccessManager::VIEW]);
        $this->loginAs($admin);

        $this->client->request('GET', self::CONFIGURATION_URL.'?'.http_build_query([
            'simulate_order' => $order->getRef(),
            'simulate_target' => $this->statusId('paid'),
        ]));

        $result = $this->client->getCrawler()->filter('[data-testid="stockmanager-simulation-result"]');
        self::assertSame(1, $result->count(), (string) $this->client->getResponse()->getStatusCode());
        self::assertStringContainsString('the right to view orders', $result->text());
        self::assertStringNotContainsString((string) $order->getRef(), $result->text());
    }

    public function testMovingARuleUpPutsItFirst(): void
    {
        $first = $this->rule('paid', 'processing', StockOperation::Increase, position: 1);
        $second = $this->rule('paid', 'sent', StockOperation::Nothing, position: 2);
        $this->assertPageRenders(self::CONFIGURATION_URL);

        $form = $this->client->getCrawler()
            ->filter('[data-testid="stockmanager-rule-'.$second->getId().'"] form')
            ->reduce(static fn ($node): bool => str_contains((string) $node->attr('action'), '/move'))
            ->form();
        $this->client->submit($form);

        self::assertSame(
            [$second->getId(), $first->getId()],
            array_map(static fn (StockManagerRule $rule): int => $rule->getId(), iterator_to_array(StockManagerRuleQuery::create()->inEvaluationOrder()->find())),
        );
    }

    public function testDeletingARuleRemovesIt(): void
    {
        $rule = $this->rule('paid', 'processing', StockOperation::Increase, position: 1);
        $this->assertPageRenders(self::CONFIGURATION_URL);

        $form = $this->client->getCrawler()
            ->filter('[data-testid="stockmanager-rule-'.$rule->getId().'"] form')
            ->reduce(static fn ($node): bool => str_contains((string) $node->attr('action'), '/delete'))
            ->form();
        $this->client->submit($form);

        self::assertNull(StockManagerRuleQuery::create()->findPk($rule->getId()));
    }

    public function testARuleWhoseStatusWasDeletedIsFlagged(): void
    {
        $rule = $this->rule('paid', 'processing', StockOperation::Increase, position: 1);
        $rule->setTargetStatusId(null)->save();

        $this->assertPageRenders(self::CONFIGURATION_URL);

        self::assertSame(
            1,
            $this->client->getCrawler()->filter('[data-testid="stockmanager-rule-'.$rule->getId().'"] [data-testid="stockmanager-rule-broken"]')->count(),
        );
    }

    public function testTheSimulationOfATransitionNamesTheRuleThatDecides(): void
    {
        $this->rule('paid', 'sent', StockOperation::Nothing, position: 1);

        $this->assertPageRenders(self::CONFIGURATION_URL.'?'.http_build_query([
            'simulate_source' => $this->statusId('paid'),
            'simulate_target' => $this->statusId('sent'),
            'simulate_payment' => 0,
        ]));

        $result = $this->client->getCrawler()->filter('[data-testid="stockmanager-simulation-rule"]');
        self::assertSame(1, $result->count());
        self::assertStringContainsString('#1', $result->text());
        self::assertSame(
            (string) $this->statusId('sent'),
            $this->client->getCrawler()->filter('#stockmanager-simulate-target option[selected]')->attr('value'),
            'The form shows the question it answered.',
        );
    }

    public function testTheSimulationOfAnOrderSaysWhatItsStatusChangeDoesToTheStock(): void
    {
        $order = $this->factory()->order();

        $this->assertPageRenders(self::CONFIGURATION_URL.'?'.http_build_query([
            'simulate_order' => $order->getRef(),
            'simulate_target' => $this->statusId('paid'),
        ]));

        $crawler = $this->client->getCrawler();
        self::assertSame(1, $crawler->filter('[data-testid="stockmanager-simulation-core"]')->count(), 'No rule: the core decides.');
        self::assertStringContainsString('out of the stock', $crawler->filter('[data-testid="stockmanager-simulation-operation"]')->text());
    }

    private function factory(): FixtureFactory
    {
        return new FixtureFactory($this->getPropelConnection());
    }

    private function loginAs(Admin $admin): void
    {
        $admin->eraseCredentials();
        $this->injector?->setAdmin($admin);
    }

    /**
     * @param list<string> $accesses
     */
    private function grantTheModule(Admin $admin, array $accesses): void
    {
        $accessManager = new AccessManager(0);
        $accessManager->build($accesses);

        (new ProfileModule())
            ->setProfileId($admin->getProfileId())
            ->setModuleId(ModuleQuery::create()->findOneByCode('StockManager', $this->getPropelConnection())?->getId())
            ->setAccess($accessManager->getAccessValue())
            ->save($this->getPropelConnection());
    }

    private function rule(string $from, string $to, StockOperation $operation, int $position): StockManagerRule
    {
        $rule = (new StockManagerRule())
            ->setPosition($position)
            ->setSourceStatusId($this->statusId($from))
            ->setTargetStatusId($this->statusId($to))
            ->setOperation($operation->value);
        $rule->save();

        return $rule;
    }

    private function statusId(string $code): int
    {
        $status = OrderStatusQuery::create()->findOneByCode($code);
        self::assertNotNull($status);

        return $status->getId();
    }
}
