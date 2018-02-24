<?php
namespace Alexrowesoap\Payumsagepay\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Exception\RequestNotSupportedException;
use Alexrowesoap\Payumsagepay\Api;
use Alexrowesoap\Payumsagepay\Api\State\StateInterface;

class CaptureAction implements ActionInterface
{
    use GatewayAwareTrait;

    /**
     * @var Api
     */
    protected $api;

    /**
     * {@inheritDoc}
     */
    public function setApi($api)
    {
        if (false === $api instanceof Api) {
            throw new UnsupportedApiException('Not supported.');
        }

        $this->api = $api;
    }

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        $token = $request->getToken();

        if (isset($model['Status'])) {
            return;
        }

        $details = $this->api->prepareOffsiteDetails($model->toUnsafeArray()); // filter model details for request

        $missing = $this->api->getMissingDetails($details); // sagepay's api is wayward we should check for presence of required minimum

        if (count($missing) > 0) {
            throw new LogicException(
                sprintf(
                    'Missing: %s details are mandatory for current payment request!',
                    implode(", ", array_keys($missing))
                )
            );
        }

        $model['state'] = StateInterface::STATE_WAITING;

        // we do not need any sync action
        // but payum by default do not set any afterUrl to notify token
        if ($token) {
            $model['afterUrl'] = $token->getAfterUrl();
        }

        $response = $this->api->createOffsitePurchase($details);

        $responseArr = json_decode( $response->getBody()->getContents() );
        $model['state'] = $responseArr['Status'] ==
        Api::STATUS_OK || $responseArr['Status'] == Api::STATUS_OK_REPEATED ?
            StateInterface::STATE_REPLIED :
            StateInterface::STATE_ERROR;

        /*$model->replace(
            array_merge(
                (array) $model->toUnsafeArray(),
                (array) $response->toArray()
            )
        );*/

        if ($responseArr['Status'] == Api::STATUS_OK ||
            $responseArr['Status'] == Api::STATUS_OK_REPEATED
        ) {

            throw new HttpRedirect(
                $responseArr['NextURL'] . '=' . $responseArr['VPSTxId']
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
