<?php
namespace Alexrowesoap\Payumsagepay;

use Alexrowesoap\Payumsagepay\Action\AuthorizeAction;
use Alexrowesoap\Payumsagepay\Action\CancelAction;
use Alexrowesoap\Payumsagepay\Action\ConvertPaymentAction;
use Alexrowesoap\Payumsagepay\Action\CaptureAction;
use Alexrowesoap\Payumsagepay\Action\NotifyAction;
use Alexrowesoap\Payumsagepay\Action\RefundAction;
use Alexrowesoap\Payumsagepay\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class PayumsagepayGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name' => 'payumsagepay',
            'payum.factory_title' => 'sagepay',
            'payum.action.capture' => new CaptureAction(),
            'payum.action.authorize' => new AuthorizeAction(),
            'payum.action.refund' => new RefundAction(),
            'payum.action.cancel' => new CancelAction(),
            'payum.action.notify' => new NotifyAction(),
            'payum.action.status' => new StatusAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = array(
                'sandbox' => true,
            );
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = [];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api((array) $config, $config['payum.http_client'], $config['httplug.message_factory']);
            };
        }
    }
}
