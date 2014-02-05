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
		$redcoreLoader = JPATH_LIBRARIES . '/redcore/bootstrap.php';

		if (file_exists($redcoreLoader) && !class_exists('Inflector'))
		{
			require_once $redcoreLoader;

			// For Joomla! 2.5 compatibility we add some core functions
			if (version_compare(JVERSION, '3.0', '<'))
			{
				RLoader::registerPrefix('J',  JPATH_LIBRARIES . '/redcore/joomla', false, true);
			}

			// Do the checks
			$valid = RHelperCurrency::isValid($currencyFrom);

			if (!$valid)
			{
				throw new Exception(sprintf('Missing or invalid currency code to convert from (%s)', $currencyFrom));
			}

			$valid = RHelperCurrency::isValid($currencyTo);

			if (!$valid)
			{
				throw new Exception(sprintf('Missing or invalid currency code to convert to (%s)', $currencyTo));
			}
		}
		else
		{
			$app = JFactory::getApplication();
			$app->enqueueMessage('Currency converter skipping checks - install redCORE library to enable code checking');
		}

		if (!$currencyFrom)
		{
			throw new Exception('Missing currency source for conversion');
		}

		if (!$currencyTo)
		{
			throw new Exception('Missing currency target for conversion');
		}

		if ($currencyFrom == $currencyTo || !$amount)
		{
			$res = $amount;
		}

		$rate = $this->getRate($currencyFrom, $currencyTo);
		$res = $amount * $rate;

		return true;
	}

	/**
	 * Get the rate
	 *
	 * @param   string  $currencyFrom  the currency code to convert from
	 * @param   string  $currencyTo    the currency code to convert to
	 *
	 * @throws Exception
	 *
	 * @return float rate
	 */
	protected function getRate($currencyFrom, $currencyTo)
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
