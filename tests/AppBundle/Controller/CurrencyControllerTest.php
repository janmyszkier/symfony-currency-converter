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

    public function testRUBToPLNConversion()
    {
        $client = static::createClient();
        $client->request('POST', '/query', ['from' => 'RUB', 'to' => 'PLN', 'amount' => 123.45]);
        $conversionResult = json_decode($client->getResponse()->getContent());

        $this->assertGreaterThan(6.50, $conversionResult->afterConversion);
    }

    public function testUnsupportedSourceCurrency()
    {
        $client = static::createClient();
        $client->request('POST', '/query', ['from' => 'ABC', 'to' => 'PLN', 'amount' => 100]);
        $conversionResult = json_decode($client->getResponse()->getContent());

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertObjectHasAttribute('error', $conversionResult);
        $this->assertEquals('source_currency_not_supported', $conversionResult->error);
    }

    public function testUnsupportedTargetCurrency()
    {
        $client = static::createClient();
        $client->request('POST', '/query', ['from' => 'USD', 'to' => 'ABC', 'amount' => 100]);
        $conversionResult = json_decode($client->getResponse()->getContent());

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertObjectHasAttribute('error', $conversionResult);
        $this->assertEquals('target_currency_not_supported', $conversionResult->error);
    }
}
