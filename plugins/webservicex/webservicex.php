<?php
/**
 * @package    CurrencyConverter.Plugins
 *
 * @copyright  Copyright (C) 2013 redCOMPONENT.com. All rights reserved.
 * @license    GNU General Public License version 2 or later, see LICENSE.
 */

defined('JPATH_BASE') or die;

/**
 * Currency Converter plugin for webservicex
 *
 * @package  CurrencyConverter.Plugins
 * @since    1.0
 */
class PlgCurrencyConverterWebservicex extends JPlugin
{
	/**
	 * Name of the plugin
	 */
	protected $name = 'webservicex';

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
		$file = $tmp_path . '/webservicex/' . $currencyFrom . $currencyTo;

		if ($caching && is_readable($file) && (time() - filemtime($file) < $caching * 60))
		{
			// Retrieve from cache
			$rate = (float) file_get_contents($file);

			return $rate;
		}

		$url = 'http://www.webservicex.net/CurrencyConvertor.asmx/ConversionRate?FromCurrency=' . $currencyFrom . '&ToCurrency=' . $currencyTo;
		$xml = simpleXML_load_file($url, "SimpleXMLElement", LIBXML_NOCDATA);

		if ($xml === false)
		{
			throw new Exception(sprintf('Webservice convertor failed converting from %s to %s', $currencyFrom, $currencyTo));
		}

		$rate = (float) $xml;

		if ($caching)
		{
			if (file_exists($file))
			{
				unlink($file);
			}

			JFile::write($file, $rate);
		}

		return $rate;
	}
}
