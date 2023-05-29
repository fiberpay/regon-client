<?php /** @noinspection PhpUnused */


namespace Fiberpay\RegonClient;


use Fiberpay\RegonClient\Exceptions\EntityNotFoundException;
use Fiberpay\RegonClient\Exceptions\InvalidEntityIdentifierException;
use Fiberpay\RegonClient\Exceptions\RegonServiceCallFailedException;
use InvalidArgumentException;
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

    public const REPORT_TYPE_ENTITY_TYPE = 'BIR11TypPodmiotu';
    public const REPORT_TYPE_LEGAL_PERSON = 'BIR11OsPrawna';
    public const REPORT_TYPE_LEGAL_PERSON_PKD = 'BIR11OsPrawnaPkd';
    public const REPORT_TYPE_NATURAL_PERSON_GENERAL_DATA = 'BIR11OsFizycznaDaneOgolne';
    public const REPORT_TYPE_NATURAL_PERSON_CEIDG = 'BIR11OsFizycznaDzialalnoscCeidg';
    public const REPORT_TYPE_NATURAL_PERSON_PKD = 'BIR11OsFizycznaPkd';
    public const REPORT_TYPE_NATURAL_PERSON_AGRICULTURAL_ACTIVITY = 'BIR11OsFizycznaDzialalnoscRolnicza';
    public const REPORT_TYPE_NATURAL_PERSON_OTHER_ACTIVITY = 'BIR11OsFizycznaDzialalnoscPozostala';
    public const REPORT_TYPE_NATURAL_PERSON_DELETED_ACTIVITY = 'BIR11OsFizycznaDzialalnoscSkreslona';

    private const VALID_REPORTS = [
        self::REPORT_TYPE_ENTITY_TYPE,
        self::REPORT_TYPE_LEGAL_PERSON,
        self::REPORT_TYPE_LEGAL_PERSON_PKD,
        self::REPORT_TYPE_NATURAL_PERSON_GENERAL_DATA,
        self::REPORT_TYPE_NATURAL_PERSON_CEIDG,
        self::REPORT_TYPE_NATURAL_PERSON_PKD,
        self::REPORT_TYPE_NATURAL_PERSON_AGRICULTURAL_ACTIVITY,
        self::REPORT_TYPE_NATURAL_PERSON_OTHER_ACTIVITY,
        self::REPORT_TYPE_NATURAL_PERSON_DELETED_ACTIVITY,
    ];

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

    /**
     * @param bool $isProduction
     * @param string|null $client_key
     */
    public function __construct(bool $isProduction = false, string $client_key = null)
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
     * @param string $regon
     * @return array
     * @throws EntityNotFoundException
     * @throws RegonServiceCallFailedException
     */
    public function findByRegon(string $regon): array
    {
        $this->validateRegon($regon);
        return $this->findById('Regon', $regon);
    }

    /**
     * @param string $nip
     * @return array
     * @throws EntityNotFoundException
     * @throws RegonServiceCallFailedException
     */
    public function findByNip(string $nip): array
    {
        $this->validateNip($nip);
        return $this->findById('Nip', $nip);
    }

    /**
     * @param $id
     * @param $value
     * @return array
     * @throws EntityNotFoundException
     * @throws RegonServiceCallFailedException
     */
    private function findById($id, $value): array
    {
        $session = $this->signUp();
        try {
            $client = $this->createSoapClient(self::FIND_ACTION, $session);
            $result = $client->DaneSzukajPodmioty(['pParametryWyszukiwania' => [$id => $value]]);
            $data = simplexml_load_string($result->DaneSzukajPodmiotyResult)->dane;

            if (property_exists($data, 'ErrorCode')) {
                if ($data->ErrorCode == "4") {
                    throw new EntityNotFoundException($data->ErrorMessagePL);
                }
                throw new RegonServiceCallFailedException($data->ErrorMessagePl);
            }
            return $this->toArray($data);

        } catch (SoapFault $e) {
            $this->handleSoapFault($e);
        }
    }

    /**
     * @param $regon
     * @param $reportType
     * @return array
     * @throws RegonServiceCallFailedException|EntityNotFoundException
     */
    public function getReport($regon, $reportType): array
    {
        $this->validateRegon($regon);
        $this->validateReportType($reportType);

        $session = $this->signUp();
        try {
            $client = $this->createSoapClient(self::FULL_REPORT_ACTION, $session);
            $result = $client->DanePobierzPelnyRaport(['pRegon' => $regon, 'pNazwaRaportu' => $reportType]);
            if ($reportType === self::REPORT_TYPE_NATURAL_PERSON_PKD || $reportType === self::REPORT_TYPE_LEGAL_PERSON_PKD) {
                $data = simplexml_load_string($result->DanePobierzPelnyRaportResult);
            } else {
                $data = simplexml_load_string($result->DanePobierzPelnyRaportResult)->dane;
            }

            if (property_exists($data, 'ErrorCode')) {
                if ($data->ErrorCode == "4") {
                    throw new EntityNotFoundException($data->ErrorMessagePL);
                }
                throw new RegonServiceCallFailedException($data->ErrorMessagePl);
            }

            return $this->toArray($data);

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

    private function validateReportType($reportType)
    {
        $isValid = in_array($reportType, self::VALID_REPORTS);

        if (!$isValid) {
            throw new InvalidArgumentException("$reportType is not valid report type.");
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

        if ($client_key === null) {
            throw new InvalidArgumentException("Client key is required for production use");
        }

        return $client_key;
    }

    /**
     * @param $data
     * @return array
     */
    private function toArray($data): array
    {
        return get_object_vars($data);
    }
}
