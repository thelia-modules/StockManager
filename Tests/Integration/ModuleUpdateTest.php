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

use StockManager\StockManager;
use Thelia\Test\IntegrationTestCase;

/**
 * Updating from the Thelia 2 version creates the table of the rules. DROP TABLE commits
 * on its own, so the test runs outside a transaction and puts the table back itself.
 */
final class ModuleUpdateTest extends IntegrationTestCase
{
    protected bool $useTransaction = false;

    protected function tearDown(): void
    {
        (new StockManager())->postActivation($this->getPropelConnection());

        parent::tearDown();
    }

    public function testUpdatingFromVersion2CreatesTheTableOfTheRules(): void
    {
        $connection = $this->getPropelConnection();
        $connection->exec('DROP TABLE IF EXISTS `stock_manager_rule`');
        self::assertFalse($this->tableExists());

        (new StockManager())->update('2.0.1', '3.0.0', $connection);

        self::assertTrue($this->tableExists());
    }

    public function testTheUpdateScriptCreatesTheTableTheInstallScriptCreates(): void
    {
        self::assertSame(
            file_get_contents(__DIR__.'/../../Config/thelia.sql'),
            file_get_contents(__DIR__.'/../../Config/update/3.0.0.sql'),
            'A shop updated from version 2 gets the table a new shop gets.',
        );
    }

    private function tableExists(): bool
    {
        // A Propel statement gives '' rather than false when there is no row.
        return 'stock_manager_rule' === $this->getPropelConnection()->query("SHOW TABLES LIKE 'stock_manager_rule'")->fetchColumn();
    }
}
