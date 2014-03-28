<?php
/**
 * @package    CurrencyConverter.Plugins
 *
 * @copyright  Copyright (C) 2013 redCOMPONENT.com. All rights reserved.
 * @license    GNU General Public License version 2 or later, see LICENSE.
 */

defined('JPATH_BASE') or die;

/**
 * Currency Converter plugin for europeancentralbank.org
 *
 * @package  CurrencyConverter.Plugins
 * @since    1.0
 */
class PlgCurrencyConverterEuropeancentralbank extends JPlugin
{
	/**
	 * Name of the plugin
	 */
	protected $name = 'europeancentralbank';

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
			throw new RuntimeException('Install redCORE library to enable currency conversion');
		}

		// Bootstraps redCORE
		RBootstrap::bootstrap();
	}

	/**
	 * Make sure a currency code is valid
	 *
	 * @param   string  $code  currency code to validate
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
	 * @throws Exception
	 *
	 * @return float rate
	 */
	protected function getRatio($currencyFrom, $currencyTo)
	{
		$caching = (int) $this->params->get('caching', 0);
		$tmp_path = JFactory::getApplication()->getCfg('tmp_path');
		$file = $tmp_path . '/europeancentralbank/data.txt';

		if ($caching && is_readable($file) && (time() - filemtime($file) < $caching * 60))
		{
			// Retrieve from cache
			$xml = file_get_contents($file);
		}
		else
		{
			$url = "http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml";

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			// Get the data:
			$xml = curl_exec($ch);
			curl_close($ch);

			if ($caching)
			{
				if (file_exists($file))
				{
					unlink($file);
				}

				JFile::write($file, $xml);
			}
		}

		// Decode response:
		$exchangeRates = simplexml_load_string($xml);

		$rates = array('EUR' => 1);

		foreach ($exchangeRates->Cube->Cube->Cube as $currency)
		{
			$rates[(string) $currency['currency']] = (float) $currency['rate'];
		}

		if (!isset($rates[$currencyFrom]))
		{
			throw new Exception(sprintf('%s currency is not supported by europeancentralbank', $currencyFrom));
		}

		if (!isset($rates[$currencyTo]))
		{
			throw new Exception(sprintf('%s currency is not supported by europeancentralbank', $currencyTo));
		}

		$rate = $rates[$currencyTo] / $rates[$currencyFrom];

		return $rate;
	}
}
