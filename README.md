CurrencyConverters
==================

This repository contains joomla currencyconverter type plugins, being used to convert currencies.
Each plugin implements the interface with a specific service

Implementation
--------------
each plugin just needs to implement the onCurrencyConvert method
```php
  public function onCurrencyConvert($amount, $currencyFrom, $currencyTo, &$res)
```
preferably, implement some kind of caching too

Usage
-----
```php
  JPluginHelper::importPlugin('currencyconverter');
  $dispatcher = JDispatcher::getInstance();
  
  $price = false;
  $dispatcher->trigger('onCurrencyConvert', array($amount, $currencyFrom, $currencyTo, &$price));
```
