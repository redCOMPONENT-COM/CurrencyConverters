CurrencyConverters
==================

plugins to convert currencies, one per available service

each plugin just needs to implement the onCurrencyConvert method

  public function onCurrencyConvert($amount, $currencyFrom, $currencyTo, &$res)
  
preferably, implement some kind of caching too
