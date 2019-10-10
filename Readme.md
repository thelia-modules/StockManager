# Stock Manager

Choose stock actions to do when an order status change.
On Thelia >= 2.4.0  You can also choose what to do on order creation for each payment modules.

## Installation

### Manually

* Copy the module into ```<thelia_root>/local/modules/``` directory and be sure that the name of the module is StockManager.
* Activate it in your thelia administration panel

### Composer

* Add it in your main thelia composer.json file

```
composer require thelia/stock-manager-module:~1.0.0
```
* Activate it in your thelia administration panel

## Usage

Go to `Configuration` in Thelia left menu you will see a new link to Stock manager in `ORDER PATH CONFIGURATION` section
On this page you can configure :
* For which module we want to decrement stock on order creattion (only for Thelia >= 2.4.0)
* Operation to do on stock (increment / decrement) for a defined status path