<?php

namespace AppBundle\Controller;

use AppBundle\Service\ApiLayerConverter\SourceCurrencyNotSupportedException;
use AppBundle\Service\ApiLayerConverter\TargetCurrencyNotSupportedException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/query")
     */
    public function queryAction(Request $request): JsonResponse
    {
        $converter = $this->get('api_layer_converter');
        $fromCurrency = $request->get('from');
        $toCurrency = $request->get('to');
        $amount = $request->get('amount');

        try {
            $convertedAmount = $converter->convert($amount, $fromCurrency, $toCurrency);
        } catch (TargetCurrencyNotSupportedException $e) {
            return new JsonResponse(array('error' => 'target_currency_not_supported'), 400);
        } catch (SourceCurrencyNotSupportedException $e) {
            return new JsonResponse(array('error' => 'source_currency_not_supported'), 400);
        }

        return new JsonResponse(array('afterConversion' => $convertedAmount), 200);
    }

    /**
     * @Route("/index")
     */
    public function indexAction()
    {

        return $this->render('AppBundle:Currency:index.html.twig', array(
            'currencies' => $this->get('api_layer_converter')->getSupportedCurrencies(),
        ));
    }
}
