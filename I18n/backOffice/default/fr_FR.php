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

return [
    'Stock rules' => 'Règles de stock',
    'When an order changes status, the first rule matching the change decides what happens to the stock. Without a matching rule, Thelia decides as usual: the stock is taken when an unpaid order is paid, and given back when a paid order goes back to unpaid or is cancelled.' => 'Quand une commande change de statut, la première règle qui correspond au changement décide de ce qui arrive au stock. Sans règle correspondante, Thelia décide comme d’habitude : le stock sort quand une commande impayée est payée, et revient quand une commande payée redevient impayée ou est annulée.',
    'From' => 'De',
    'To' => 'Vers',
    'Payment module' => 'Moyen de paiement',
    'Stock' => 'Stock',
    'Any' => 'Tous',
    'Deleted status' => 'Statut supprimé',
    'Deleted module' => 'Module supprimé',
    'Ignored:' => 'Ignorée :',
    'Its starting status was deleted.' => 'son statut de départ a été supprimé.',
    'Its target status was deleted.' => 'son statut d’arrivée a été supprimé.',
    'Its payment module was deleted.' => 'son moyen de paiement a été supprimé.',
    'Its operation is unknown.' => 'son opération est inconnue.',
    'Move up' => 'Monter',
    'Move down' => 'Descendre',
    'There is no rule: Thelia decides for every status change.' => 'Aucune règle : Thelia décide pour chaque changement de statut.',
    'Add a rule' => 'Ajouter une règle',
    'Add the rule' => 'Ajouter la règle',
    'Take the products out of the stock' => 'Sortir les produits du stock',
    'Put the products back in the stock' => 'Remettre les produits en stock',
    'Leave the stock as it is' => 'Ne pas toucher au stock',
    'Simulate a status change' => 'Simuler un changement de statut',
    'Give an order to know what its change of status would do to the stock, or a starting status to know which rule would decide. The simulation changes neither the order nor the stock.' => 'Indiquez une commande pour savoir ce que son changement de statut ferait au stock, ou un statut de départ pour savoir quelle règle déciderait. La simulation ne modifie ni la commande ni le stock.',
    'Reading an order needs the right to view orders.' => 'Lire une commande demande le droit de consulter les commandes.',
    'Order reference' => 'Référence de commande',
    'or from' => 'ou depuis',
    'paid with' => 'payée par',
    'Simulate' => 'Simuler',
    'Choose a target status.' => 'Choisissez un statut d’arrivée.',
    'No order has this reference.' => 'Aucune commande ne porte cette référence.',
    'Choose an order, or a starting status.' => 'Choisissez une commande, ou un statut de départ.',
    'Order %ref%' => 'Commande %ref%',
    'The order is already in this status: nothing happens.' => 'La commande est déjà dans ce statut : il ne se passe rien.',
    'This change of status is not allowed by the transitions of the shop.' => 'Les transitions de la boutique n’autorisent pas ce changement de statut.',
    'Rule #%position% decides: %operation%.' => 'La règle n° %position% décide : %operation%.',
    'No rule matches: Thelia decides.' => 'Aucune règle ne correspond : Thelia décide.',
    'No rule matches: Thelia decides, according to whether the order is paid and to its payment module.' => 'Aucune règle ne correspond : Thelia décide, selon que la commande est payée et selon son moyen de paiement.',
    '(without the rule: %operation%)' => '(sans la règle : %operation%)',
];
