<?php
namespace Alexrowesoap\Payumsagepay;

use GuzzleHttp\Psr7\Request;
use Http\Message\MessageFactory;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Core\HttpClientInterface;

use Alexrowesoap\Payumsagepay\Api\Signature\Validator;

class Api
{

    const VERSION = '3.00';
    const OPERATION_PAYMENT = 'PAYMENT';
    const OPERATION_DEFFERED = 'DEFFERED';
    const OPERATION_AUTHENTICATE = 'AUTHENTICATE';
    const OPERATION_REPEAT = 'REPEAT';
    const STATUS_OK = 'OK';
    const STATUS_PENDING = 'PENDING';
    const STATUS_OK_REPEATED = 'OK REPEATED';
    const STATUS_MALFORMED = 'MALFORMED';
    const STATUS_INVALID = 'INVALID';
    const STATUS_ERROR = 'ERROR';

    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    protected $options = array(
        'vendor' => null,
        'sandbox' => null,
    );

    /**
     * @param array               $options
     * @param HttpClientInterface $client
     * @param MessageFactory      $messageFactory
     *
     * @throws \Payum\Core\Exception\InvalidArgumentException if an option is invalid
     */
    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory)
    {
        $this->client = $client;
        $this->options = array_replace($this->options, $options);
        if (true == empty($this->options['vendor'])) {
            throw new  InvalidArgumentException('The vendor option must be set.');
        }
        if (false == is_bool($this->options['sandbox'])) {
            throw new InvalidArgumentException('The boolean sandbox option must be set.');
        }
        $this->messageFactory = $messageFactory;
    }

    /**
     * @return string
     */
    protected function getApiEndpoint()
    {
        return $this->options['sandbox'] ? 'http://sandbox.example.com' : 'http://example.com';
    }


    /**
     * @param array $paymentDetails
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function createOffsitePurchase(array $paymentDetails)
    {
        $paymentDetails['TxType'] = static::OPERATION_PAYMENT;
        $query = http_build_query($paymentDetails);
        $request = new Request(
            'post',
            $this->getOffsiteResource() . $this->getGatewayHost(),
            null,
            $query
        );
        return $this->doRequest($request);
    }
    public function getMissingDetails(array $details)
    {
        $required = array(
            'VPSProtocol' => null,
            'TxType' => null,
            'Vendor' => null,
            'VendorTxCode' => null,
            'Amount' => null,
            'Currency' => null,
            'Description' => null,
            'NotificationURL' => null,
            'BillingSurname' => null,
            'BillingFirstnames' => null,
            'BillingAddress1' => null,
            'BillingCity' => null,
            'BillingPostCode' => null,
            'BillingCountry' => null,
            'DeliverySurname' => null,
            'DeliveryFirstnames' => null,
            'DeliveryAddress1' => null,
            'DeliveryCity' => null,
            'DeliveryPostCode' => null,
            'DeliveryCountry' => null,
        );
        return $this->removeNullValues(array_diff_key($required, $details));
    }

    /**
     *
     * @param Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function doRequest(Request $request)
    {
        $response = $this->client->send($request);
        return $response;
    }
    /**
     * @param array $paymentDetails
     * @return array
     */
    public function prepareOffsiteDetails(array $paymentDetails)
    {
        $supportedParams = array(
            'VendorTxCode' => null,
            'Amount' => null,
            'Currency' => null,
            'Description' => null,
            'NotificationURL' => null,
            'Token' => null,
            'BillingSurname' => null,
            'BillingFirstnames' => null,
            'BillingAddress1' => null,
            'BillingAddress2' => null,
            'BillingCity' => null,
            'BillingPostCode' => null,
            'BillingCountry' => null,
            'BillingState' => null,
            'BillingPhone' => null,
            'DeliverySurname' => null,
            'DeliveryFirstnames' => null,
            'DeliveryAddress1' => null,
            'DeliveryAddress2' => null,
            'DeliveryCity' => null,
            'DeliveryPostCode' => null,
            'DeliveryCountry' => null,
            'DeliveryState' => null,
            'DeliveryPhone' => null,
            'CustomerEMail' => null,
            'Basket' => null,
            'AllowGiftAid' => null,
            'ApplyAVSCV2' => null,
            'Apply3DSecure' => null,
            'Profile' => null,
            'BillingAgreement' => null,
            'AccountType' => null,
            'CreateToken' => null,
            'StoreToken' => null,
            'BasketXML' => null,
            'CustomerXML' => null,
            'SurchargeXML' => null,
            'VendorData' => null,
            'ReferrerID' => null,
            'Language' => null,
            'Website' => null,
            'FIRecipientAcctNumber' => null,
            'FIRecipientSurname' => null,
            'FIRecipientPostcode' => null,
            'FIRecipientDoB' => null,
        );
        $paymentDetails = array_filter(
            array_replace(
                $supportedParams,
                array_intersect_key($paymentDetails, $supportedParams)
            )
        );
        $paymentDetails['TxType'] = static::OPERATION_PAYMENT;
        $paymentDetails = $this->appendGlobalParams($paymentDetails);
        return $paymentDetails;
    }
    public function tamperingDetected(array $notification, array $model)
    {
        $validator = new Validator();
        $available = $validator->getAvailableParams();
        foreach ($available as $key => $value) {
            if (array_key_exists($key, $notification)) {
                $available[$key] = $notification[$key];
            }
        }
        $available['SecurityKey'] = $model['SecurityKey'];
        $available['VendorName'] = $this->options['vendor'];
        $reciviedHash = $notification['VPSSignature'];
        $validator->setParams($available);
        return $validator->tamperingDetected($reciviedHash);
    }
    protected function appendGlobalParams(array $paymentDetails = array())
    {
        $paymentDetails['VPSProtocol'] = self::VERSION;
        $paymentDetails['Vendor'] = $this->options['vendor'];
        return $paymentDetails;
    }
    protected function getGatewayHost()
    {
        return $this->options['sandbox'] ?
            'https://test.sagepay.com' :
            'https://live.sagepay.com'
            ;
    }
    protected function getOffsiteResource()
    {
        return '/gateway/service/vspserver-register.vsp';
    }
    protected function removeNullValues($params)
    {
        $cleared = array();
        foreach ($params as $key => $value) {
            if ($value != null) {
                $cleared[$key] = $value;
            }
        }
        return $cleared;
    }
}
