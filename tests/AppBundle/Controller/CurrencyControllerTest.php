<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CurrencyControllerTest extends WebTestCase
{
    public function testUSDToPLNConversion()
    {
        $client = static::createClient();
        $client->request('POST', '/query', ['from' => 'USD', 'to' => 'PLN', 'amount' => 100]);
        $conversionResult = json_decode($client->getResponse()->getContent());

        $this->assertGreaterThan(350.00, $conversionResult->afterConversion);
    }

    public function testUnsupportedSourceCurrency()
    {
        $client = static::createClient();
        $client->request('POST', '/query', ['from' => 'EUR', 'to' => 'PLN', 'amount' => 100]);
        $conversionResult = json_decode($client->getResponse()->getContent());

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertObjectHasAttribute('error', $conversionResult);
        $this->assertEquals('source_currency_not_supported', $conversionResult->error);
    }

    public function testUnsupportedTargetCurrency()
    {
        $client = static::createClient();
        $client->request('POST', '/query', ['from' => 'USD', 'to' => 'BTC', 'amount' => 100]);
        $conversionResult = json_decode($client->getResponse()->getContent());

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertObjectHasAttribute('error', $conversionResult);
        $this->assertEquals('target_currency_not_supported', $conversionResult->error);
    }
}
