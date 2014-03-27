<?php
/**
 * @package    CurrencyConverter.Plugins
 *
 * @copyright  Copyright (C) 2013 redCOMPONENT.com. All rights reserved.
 * @license    GNU General Public License version 2 or later, see LICENSE.
 */

defined('JPATH_BASE') or die;

/**
 * Currency Converter plugin for openexchangerates.org
 *
 * @package  CurrencyConverter.Plugins
 * @since    1.0
 */
class PlgCurrencyConverterOpenexchangerates extends JPlugin
{
	/**
	 * Name of the plugin
	 */
	protected $name = 'openexchangerates';

	/**
	 * @var rates data
	 */
	protected $data;

	/**
	 * Converts an amount between currencies
	 *
	 * @param   float   $amount        the amount to convert
	 * @param   string  $currencyFrom  the currency code to convert from
	 * @param   string  $currencyTo    the currency code to convert to
	 * @param   float   &$res          the result
	 *
	 * @return bool true on success
	 *
	 * @throws Exception
	 */
	public function onCurrencyConvert($amount, $currencyFrom, $currencyTo, &$res)
	{
		$this->loadRedcore();
		$this->validateCurrency($currencyFrom);
		$this->validateCurrency($currencyTo);

		$rate = $this->getRatio($currencyFrom, $currencyTo);
		$precision = RHelperCurrency::getPrecision($currencyTo);

		$res = round($amount * $rate, $precision);

		return true;
	}

	/**
	 * Load redCORE
	 *
	 * @return void
	 */
	protected function loadRedcore()
	{
		$redcoreLoader = JPATH_LIBRARIES . '/redcore/bootstrap.php';

		if (!file_exists($redcoreLoader) || !JPluginHelper::isEnabled('system', 'redcore'))
		{
			$app = JFactory::getApplication();
			$app->enqueueMessage('Currency converter skipping checks - install redCORE library to enable code checking');
		}

		// Bootstraps redCORE
		RBootstrap::bootstrap();
	}

	/**
	 * Make sure a currency code is valid
	 *
	 * @param   string  $code  currency code to validate
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function validateCurrency($code)
	{
		if (!RHelperCurrency::isValid($code))
		{
			throw new Exception(sprintf('Missing or invalid currency code to convert from (%s)', $code));
		}
	}

	/**
	 * Get the ratio
	 *
	 * @param   string  $currencyFrom  the currency code to convert from
	 * @param   string  $currencyTo    the currency code to convert to
	 *
	 * @return float rate
	 */
	protected function getRatio($currencyFrom, $currencyTo)
	{
		if ($currencyFrom == $currencyTo)
		{
			return 1;
		}

		return $this->getRate($currencyTo) / $this->getRate($currencyFrom);
	}

	/**
	 * Get a currency rate
	 *
	 * @param   string  $currency  the currency code to convert from
	 *
	 * @throws Exception
	 *
	 * @return float rate
	 */
	protected function getRate($currency)
	{
		$exchangeRates = $this->getRates();

		if (!isset($exchangeRates->{$currency}))
		{
			throw new Exception(sprintf('%s currency is not supported by openexchangerates', $currency));
		}

		return (float) $exchangeRates->{$currency};
	}

	/**
	 * Fetch data
	 *
	 * @return object rates
	 *
	 * @throws Exception
	 */
	protected function getRates()
	{
		if (!$this->data)
		{
			$caching = (int) $this->params->get('caching', 0);
			$tmp_path = JFactory::getApplication()->getCfg('tmp_path');
			$file = $tmp_path . '/openexchangerates/data.txt';

			if ($caching && is_readable($file) && (time() - filemtime($file) < $caching * 60))
			{
				// Retrieve from cache
				$json = file_get_contents($file);
			}
			else
			{
				if (!$appId = $this->params->get('appId'))
				{
					throw new Exception('Missing appId for openexchangerates currency convertor plugin');
				}

				$url = "http://openexchangerates.org/api/latest.json?app_id=" . $appId;

				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

				// Get the data:
				$json = curl_exec($ch);
				curl_close($ch);

				if ($caching)
				{
					if (file_exists($file))
					{
						unlink($file);
					}

					JFile::write($file, $json);
				}
			}

			// Decode JSON response:
			$this->data = json_decode($json)->rates;
		}

		return $this->data;
	}
}
