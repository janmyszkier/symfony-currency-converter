<?php

namespace AppBundle\Service;

interface CurrencyConverterInterface
{

    public function convert($from, $to, $amount): float;

}