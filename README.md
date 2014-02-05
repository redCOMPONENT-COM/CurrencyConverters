CurrencyConverters
==================

This repository contains joomla currencyconverter type plugins, being used to convert currencies.
Each plugin implements the interface with a specific service.

The goal here of course is to provide a unique set of plugins that can be used by any developper that needs currency converting in its joomla extensions.

These plugin **require redCORE library** installed (to check on Currency codes validity)

Compatible with Joomla 2.5 and later

Implementation
--------------
each plugin just needs to implement the onCurrencyConvert method
```php
  public function onCurrencyConvert($amount, $currencyFrom, $currencyTo, &$res)
```
preferably, implement some kind of caching too

Usage
-----
For developpers
```php
  JPluginHelper::importPlugin('currencyconverter');
  $dispatcher = JDispatcher::getInstance();
  
  $price = false;
  $dispatcher->trigger('onCurrencyConvert', array($amount, $currencyFrom, $currencyTo, &$price));
```

Then, you can either pack it with your extensions, or have your users to install them themselves.
