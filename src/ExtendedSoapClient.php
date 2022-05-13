<?php

namespace Fiberpay\RegonClient;

/**
 * @method DanePobierzPelnyRaport(array $array)
 * @method DaneSzukajPodmioty(array[] $array)
 * @method Zaloguj(string[] $array)
 */
class ExtendedSoapClient extends \SoapClient
{
    public function __doRequest($request, $location, $action, $version, $oneWay = 0): ?string
    {
        $response = parent::__doRequest($request, $location, $action, $version, $oneWay);
        if (strpos($response, "Content-Type: application/xop+xml") !== false) {
            $response = stristr(stristr($response, "<s:"), "</s:Envelope>", true) . "</s:Envelope>";

        }
        return $response;
    }

}
