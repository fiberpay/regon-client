<?php /** @noinspection PhpUnused */


namespace Fiberpay\RegonClient;


use Exception;
use Fiberpay\RegonClient\Exceptions\InvalidEntityIdentifierException;
use Fiberpay\RegonClient\Exceptions\RegonServiceCallFailedException;
use SoapFault;
use SoapHeader;

class RegonClient
{

    private string $wsdlUrl;
    private string $serviceUrl;
    private string $clientKey;

    private const LOGIN_ACTION = 'http://CIS/BIR/PUBL/2014/07/IUslugaBIRzewnPubl/Zaloguj';
    private const FIND_ACTION = 'http://CIS/BIR/PUBL/2014/07/IUslugaBIRzewnPubl/DaneSzukajPodmioty';
    private const FULL_REPORT_ACTION = 'http://CIS/BIR/PUBL/2014/07/IUslugaBIRzewnPubl/DanePobierzPelnyRaport';

    private const ENV_PRODUCTION = 'production';
    private const ENV_TEST = 'test';

    private const WSDL_URL = [
        self::ENV_PRODUCTION => 'https://wyszukiwarkaregon.stat.gov.pl/wsBIR/wsdl/UslugaBIRzewnPubl-ver11-prod.wsdl',
        self::ENV_TEST => 'https://wyszukiwarkaregon.stat.gov.pl/wsBIR/wsdl/UslugaBIRzewnPubl-ver11-prod.wsdl',
    ];

    private const SERVICE_URL = [
        self::ENV_PRODUCTION => 'https://wyszukiwarkaregon.stat.gov.pl/wsBIR/UslugaBIRzewnPubl.svc',
        self::ENV_TEST => 'https://wyszukiwarkaregon.stat.gov.pl/wsBIR/UslugaBIRzewnPubl.svc',
    ];

    private const TEST_CLIENT_KEY = 'abcde12345abcde12345';

    public function __construct($isProduction, string $client_key = null)
    {
        $environment = $isProduction ? self::ENV_PRODUCTION : self::ENV_TEST;

        $validClientKey = $this->validateAndGetClientKey($isProduction, $client_key);

        $this->wsdlUrl = self::WSDL_URL[$environment];
        $this->serviceUrl = self::SERVICE_URL[$environment];
        $this->clientKey = $validClientKey;
    }

    /**
     * @throws RegonServiceCallFailedException
     */
    private function signUp()
    {
        try {
            $client = $this->createSoapClient(self::LOGIN_ACTION);
            $result = $client->Zaloguj(['pKluczUzytkownika' => $this->clientKey]);

            return $result->ZalogujResult;
        } catch (SoapFault $e) {
            $this->handleSoapFault($e);
        }
    }


    /**
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function findByRegon($regon)
    {
        $this->validateRegon($regon);
        return $this->findById('Regon', $regon);
    }

    /**
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function findByNip($nip)
    {
        $this->validateNip($nip);
        return $this->findById('Nip', $nip);
    }

    /**
     * @throws Exception
     */
    private function findById($id, $value)
    {
        $session = $this->signUp();
        try {
            $client = $this->createSoapClient(self::FIND_ACTION, $session);
            $result = $client->DaneSzukajPodmioty(['pParametryWyszukiwania' => [$id => $value]]);
            $data = simplexml_load_string($result->DaneSzukajPodmiotyResult)->dane;

            if (property_exists($data, 'ErrorCode')) {
                if($data->ErrorCode == "4") {
                    throw new EntityNotFoundException($data->ErrorMessagePL);
                }
                throw new RegonServiceCallFailedException($data->ErrorMessagePl);
            }
            return json_decode(json_encode($data));

        } catch (SoapFault $e) {
            $this->handleSoapFault($e);
        }
    }

    /**
     * @throws RegonServiceCallFailedException
     * @throws Exception
     */
    public function getReport($regon): array
    {
        $this->validateRegon($regon);
        $session = $this->signUp();
        try {
            $client = $this->createSoapClient(self::FULL_REPORT_ACTION, $session);
            $result = $client->DanePobierzPelnyRaport(['pRegon' => $regon, 'pNazwaRaportu' => 'BIR11OsFizycznaDaneOgolne']);
            $data = simplexml_load_string($result->DanePobierzPelnyRaportResult)->dane;

            if (property_exists($data, 'ErrorCode')) {
                throw new Exception($data->ErrorMessagePl);
            }
            return (array)json_decode(json_encode($data));

        } catch (SoapFault $e) {
            $this->handleSoapFault($e);
        }
    }

    /**
     * @param $nip
     * @return void
     */
    private function validateNip($nip)
    {
        $pattern = '/^\d{10}$/';

        if (!(preg_match($pattern, $nip))) {
            throw new InvalidEntityIdentifierException('NIP', $nip);
        }
    }

    /**
     * @param $regon
     * @return void
     */
    private function validateRegon($regon)
    {
        $pattern = '/^(\d{9}|\d{14})$/';

        if (!(preg_match($pattern, $regon))) {
            throw new InvalidEntityIdentifierException('REGON', $regon);
        }
    }

    /**
     * @throws SoapFault
     */
    private function createSoapClient(string $action, string $sid = null): ExtendedSoapClient
    {
        $options = [
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
            'soap_version' => SOAP_1_2,
            'trace' => true,
            'exceptions' => true,
        ];

        if ($sid != null) {
            $options = $this->appendSessionIdHeader($sid, $options);
        }
        $client = new ExtendedSoapClient($this->wsdlUrl, $options);

        $namespace = 'http://www.w3.org/2005/08/addressing';
        $headers = [
            new SoapHeader($namespace, 'To', $this->serviceUrl),
            new SoapHeader($namespace, 'Action', $action),
        ];

        $client->__setSoapHeaders($headers);

        return $client;
    }

    /**
     * @param $e
     * @return mixed
     * @throws RegonServiceCallFailedException
     */
    private function handleSoapFault($e)
    {
        throw new RegonServiceCallFailedException(
            $e->getMessage(), $e->getCode(), $e
        );
    }

    /**
     * @param string $sid
     * @param array $options
     * @return array
     */
    private function appendSessionIdHeader(string $sid, array $options): array
    {
        $options['stream_context'] = stream_context_create([
            'http' => [
                'header' => 'sid: ' . $sid,
            ]
        ]);

        return $options;
    }

    private function validateAndGetClientKey($isProduction, ?string $client_key): string
    {
        if (!$isProduction) {
            return self::TEST_CLIENT_KEY;
        }

        if($client_key === null) {
            throw new \InvalidArgumentException("Client key is required for production use");
        }

        return $client_key;
    }
}
