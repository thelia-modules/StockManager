# Stock Manager

Decide which change of status takes the products of an order out of the stock, and
which one puts them back, instead of the fixed rule of Thelia.

Version 3 targets Thelia 3.2 and later. Version 2.0.1 is the Thelia 2 module.

## Installation

```
composer require thelia/stock-manager-module:^3.0
```

Then activate the module in the back office (Modules).

## Usage

The configuration page of the module (Modules, Stock Manager, Configure) lists the rules.

A rule names a starting status, a target status and what happens to the stock:

- take the products out of the stock;
- put the products back in the stock;
- leave the stock as it is.

A rule can be restricted to the orders paid with one payment module.

When an order changes status, the first rule of the list matching the change decides.
The arrows move a rule up or down the list. Without a matching rule, Thelia decides as it
always did: the stock is taken when an unpaid order is paid, and given back when a paid
order goes back to unpaid or is cancelled. With no rule at all, a shop behaves exactly as
without the module.

The simulation, at the bottom of the page, answers before anything changes:

- for an order reference and a target status, it gives the operation the change would
  apply, and the rule that decides it, or says that Thelia does;
- for a starting status, a payment module and a target status, it names the rule that
  would decide.

Each time a rule decides, the order history gets a `stock_rule_applied` line. The line
holds the rule, the transition, the operation, and what Thelia alone would have done. Its
sentence is written in the language of whoever changed the status, and stays so.

Reading an order in the simulation needs the right to view orders.

## How it works

The module listens to `TheliaEvents::ORDER_GET_STOCK_UPDATE_OPERATION_ON_ORDER_STATUS_CHANGE`
after the core (priority 64, the core decides at 128). A matching rule replaces the
operation the core chose. Every change of status that goes through `ORDER_UPDATE_STATUS`
applies the rules: the back office, the API, a payment module confirming a payment, a
scheduled command.

The event is dispatched inside the transaction of the status change. If the change fails,
the stock movement and the history line are rolled back with the status.

The simulation dispatches the same decision event as a real change of status, so that
Thelia, the rules and any other module answer as they will. It writes no history line and
moves no stock; a module that acts on that event beyond deciding the operation would act
during a simulation too.

A rule whose status or payment module was deleted stays in the list, flagged, and never
matches again.

The stock taken when the order is placed (`manageStockOnCreation` of the payment module)
is not decided by the rules.

## Upgrading from version 2

Version 2 stored its rules in the `stock_operation*` tables, with restrictions by payment
module and by carrier, and a setting per payment module for the stock taken at order
creation. Version 3 does not read them, and drops the carrier restriction and that
setting. Write the rules again on the configuration page. The old tables are left in
place: drop them once the new rules are written.

## Tests

From the root of a Thelia 3 project, with the module in `local/modules/StockManager`:

```
composer test:prepare
php vendor/bin/phpunit -c local/modules/StockManager/phpunit.xml.dist --testsuite integration
php vendor/bin/phpunit -c local/modules/StockManager/phpunit.xml.dist --testsuite http
```
