<?php

namespace AppBundle\Service;

use AppBundle\Service\ApiLayerConverter\SourceCurrencyNotSupportedException;
use AppBundle\Service\ApiLayerConverter\TargetCurrencyNotSupportedException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ApiLayerConverter implements CurrencyConverterInterface
{
    public $apiUrl = 'http://apilayer.net/api/live';

    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function convert($amount, $fromCurrency, $toCurrency): float
    {
        $this->validateRequest($amount, $fromCurrency, $toCurrency);

        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $quotes = $this->getQuotes($fromCurrency, $toCurrency);
        $rate = $quotes->{$fromCurrency . $toCurrency};
        return $rate * $amount;
    }

    private function getQuotes($fromCurrency, $toCurrency)
    {
        $apiKey = $this->container->getParameter('apilayer_api_key');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl . '?access_key=' . $apiKey . '&currencies=' . $toCurrency . '&source=' . $fromCurrency . '&format=1');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json')); // Assuming you're requesting JSON
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        $data = json_decode($result);
        if ($data->success === false) {
            throw new SourceCurrencyNotSupportedException($data->error->info);
        }
        if (!empty($data->quotes)) {
            return $data->quotes;
        }
    }

    public function getSupportedCurrencies()
    {
        return array('EUR', 'GBP', 'CAD', 'PLN', 'USD');
    }

    /* @TODO: move to validator and extend once we have access to the paid API documentation */
    private function validateRequest($amount, $fromCurrency, $toCurrency)
    {

        if (!in_array($fromCurrency, $this->getSupportedCurrencies())) {
            throw new SourceCurrencyNotSupportedException();
        }

        if (!in_array($toCurrency, $this->getSupportedCurrencies())) {
            throw new TargetCurrencyNotSupportedException();
        }
    }
}