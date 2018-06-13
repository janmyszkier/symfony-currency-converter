<?php

namespace AppBundle\Service;

use AppBundle\Service\ApiLayerConverter\SourceCurrencyNotSupportedException;
use AppBundle\Service\ApiLayerConverter\TargetCurrencyNotSupportedException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ApiLayerConverter implements CurrencyConverterInterface
{
    public $apiUrl = 'http://apilayer.net/api/';

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
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl . 'live?access_key=' . $apiKey . '&currencies=' . $toCurrency . '&source=' . $fromCurrency . '&format=1');
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
        $cache = new FilesystemAdapter('apilayer', 3600);
        $supportedCurrencies = $cache->getItem('apilayer.supported_currencies');

        if (!$supportedCurrencies->isHit()) {
            $currencies = $this->fetchCurrenciesFromApi();
            $supportedCurrencies->set($currencies);
            $cache->save($supportedCurrencies);
        }

        return $supportedCurrencies->get();
    }

    private function fetchCurrenciesFromApi()
    {
        $apiKey = $this->container->getParameter('apilayer_api_key');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl . 'list?access_key=' . $apiKey);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json')); // Assuming you're requesting JSON
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        $data = json_decode($result);
        if (!empty($data->currencies)) {
            return (array)$data->currencies;
        }
    }

    /* @TODO: move to validator and extend once we have access to the paid API documentation */
    private function validateRequest($amount, $fromCurrency, $toCurrency)
    {
        if (!in_array($fromCurrency, array_keys($this->getSupportedCurrencies()))) {
            throw new SourceCurrencyNotSupportedException();
        }

        if (!in_array($toCurrency, array_keys($this->getSupportedCurrencies()))) {
            throw new TargetCurrencyNotSupportedException();
        }
    }
}