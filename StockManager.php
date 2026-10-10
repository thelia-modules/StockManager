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

namespace StockManager;

use Propel\Runtime\Connection\ConnectionInterface;
use StockManager\DependencyInjection\Compiler\PublicServicesForTestsPass;
use StockManager\DependencyInjection\Compiler\RegisterModuleTranslationsPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\Finder\Finder;
use Thelia\Core\Install\Database;
use Thelia\Module\BaseModule;

/**
 * Lets the merchant decide which status change takes the stock of an order and which one
 * gives it back. Without a rule, the core decides as it always did.
 */
class StockManager extends BaseModule
{
    public const DOMAIN_NAME = 'stockmanager';
    public const BACK_OFFICE_DOMAIN = 'stockmanager.bo.default';

    /** The type of the order history line written when a rule decided the stock. */
    public const HISTORY_EVENT_TYPE = 'stock_rule_applied';

    /**
     * The table is created with IF NOT EXISTS: activating the module again keeps the rules.
     */
    public function postActivation(?ConnectionInterface $con = null): void
    {
        (new Database($con))->insertSql(null, [__DIR__.'/Config/thelia.sql']);
    }

    public function update($currentVersion, $newVersion, ?ConnectionInterface $con = null): void
    {
        $database = new Database($con);

        $finder = Finder::create()
            ->name('*.sql')
            ->depth(0)
            ->sortByName()
            ->in(__DIR__.'/Config/update');

        foreach ($finder as $file) {
            if (version_compare($currentVersion, $file->getBasename('.sql'), '<')) {
                $database->insertSql(null, [$file->getPathname()]);
            }
        }
    }

    public function destroy(?ConnectionInterface $con = null, $deleteModuleData = false): void
    {
        if ($deleteModuleData) {
            (new Database($con))->insertSql(null, [__DIR__.'/Config/destroy.sql']);
        }
    }

    /**
     * @return list<array{0: CompilerPassInterface, 1: string}>
     */
    public static function getCompilers(): array
    {
        return [
            [new RegisterModuleTranslationsPass(__DIR__, self::DOMAIN_NAME), PassConfig::TYPE_BEFORE_OPTIMIZATION],
            [new PublicServicesForTestsPass(), PassConfig::TYPE_BEFORE_REMOVING],
        ];
    }

    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude([
                __DIR__.'/I18n/*',
                __DIR__.'/Tests/*',
                __DIR__.'/Model/*',
                __DIR__.'/DependencyInjection/*',
                __DIR__.'/Rule/RuleDecision.php',
                __DIR__.'/Rule/StockOperation.php',
                __DIR__.'/StockManager.php',
            ])
            ->autowire(true)
            ->autoconfigure(true);
    }
}
