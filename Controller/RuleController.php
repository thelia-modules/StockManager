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

namespace StockManager\Controller;

use Propel\Runtime\Propel;
use StockManager\Model\Map\StockManagerRuleTableMap;
use StockManager\Model\StockManagerRule;
use StockManager\Model\StockManagerRuleQuery;
use StockManager\Rule\StockOperation;
use StockManager\StockManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Model\ModuleQuery;
use Thelia\Model\OrderStatusQuery;
use Thelia\Module\BaseModule;
use Thelia\Tools\TokenProvider;
use Thelia\Tools\URL;

#[Route('/admin/modules/StockManager/rules', name: 'stockmanager.rules')]
class RuleController extends BaseAdminController
{
    #[Route('', name: '.create', methods: ['POST'])]
    public function create(Request $request, Session $session, TokenProvider $tokenProvider): Response
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['StockManager'], AccessManager::CREATE)) {
            return $response;
        }

        $tokenProvider->checkToken((string) $request->request->get('_token'));

        $sourceStatusId = $request->request->getInt('source_status_id');
        $targetStatusId = $request->request->getInt('target_status_id');
        $operation = StockOperation::tryFrom((string) $request->request->get('operation'));
        $paymentModuleId = $request->request->getInt('payment_module_id');

        $error = match (true) {
            null === OrderStatusQuery::create()->findPk($sourceStatusId),
            null === OrderStatusQuery::create()->findPk($targetStatusId) => 'Choose a starting status and a target status.',
            $sourceStatusId === $targetStatusId => 'The starting status and the target status must differ.',
            null === $operation => 'Choose what the rule does to the stock.',
            0 !== $paymentModuleId && null === ModuleQuery::create()->filterByType(BaseModule::PAYMENT_MODULE_TYPE)->findPk($paymentModuleId) => 'The payment module does not exist.',
            default => null,
        };

        if (null !== $error) {
            return $this->backToTheRules($session, 'danger', $error);
        }

        (new StockManagerRule())
            ->setPosition(StockManagerRuleQuery::create()->nextPosition())
            ->setSourceStatusId($sourceStatusId)
            ->setTargetStatusId($targetStatusId)
            ->setOperation($operation->value)
            ->setPaymentRestricted(0 !== $paymentModuleId)
            ->setPaymentModuleId(0 !== $paymentModuleId ? $paymentModuleId : null)
            ->save();

        return $this->backToTheRules($session, 'success', 'The rule was added at the end of the list.');
    }

    #[Route('/{id}/delete', name: '.delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request, Session $session, TokenProvider $tokenProvider): Response
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['StockManager'], AccessManager::DELETE)) {
            return $response;
        }

        $tokenProvider->checkToken((string) $request->request->get('_token'));

        $rule = StockManagerRuleQuery::create()->findPk($id);
        if (null === $rule) {
            return $this->backToTheRules($session, 'danger', 'The rule does not exist anymore.');
        }

        $rule->delete();

        return $this->backToTheRules($session, 'success', 'The rule was deleted.');
    }

    /**
     * Moves a rule one place up or down in the evaluation order, and numbers the rules
     * again from 1 so that two rules never share a position.
     */
    #[Route('/{id}/move', name: '.move', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function move(int $id, Request $request, Session $session, TokenProvider $tokenProvider): Response
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['StockManager'], AccessManager::UPDATE)) {
            return $response;
        }

        $tokenProvider->checkToken((string) $request->request->get('_token'));

        $offset = 'up' === $request->request->get('direction') ? -1 : 1;
        $connection = Propel::getConnection(StockManagerRuleTableMap::DATABASE_NAME);
        $connection->beginTransaction();

        try {
            $rules = iterator_to_array(StockManagerRuleQuery::create()->inEvaluationOrder()->find($connection));
            $rules = array_values($rules);
            $index = array_search($id, array_map(static fn (StockManagerRule $rule) => $rule->getId(), $rules), true);

            if (false === $index) {
                $connection->rollBack();

                return $this->backToTheRules($session, 'danger', 'The rule does not exist anymore.');
            }

            if (isset($rules[$index + $offset])) {
                [$rules[$index], $rules[$index + $offset]] = [$rules[$index + $offset], $rules[$index]];
            }

            foreach ($rules as $position => $rule) {
                $rule->setPosition($position + 1)->save($connection);
            }

            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }

        return $this->backToTheRules($session, 'success', 'The order of the rules was saved.');
    }

    private function backToTheRules(Session $session, string $type, string $message): RedirectResponse
    {
        $session->getFlashBag()->add($type, Translator::getInstance()->trans($message, [], StockManager::DOMAIN_NAME));

        return new RedirectResponse(URL::getInstance()->absoluteUrl('/admin/module/'.StockManager::getModuleCode()));
    }
}
