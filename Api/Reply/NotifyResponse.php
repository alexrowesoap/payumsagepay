<?php

namespace Alexrowesoap\Payumsagepay\Api\Reply;

use InvalidArgumentException;
use Payum\Core\Reply\HttpResponse;
use Alexrowesoap\Payumsagepay\Api;

class NotifyResponse extends HttpResponse
{
    protected $params;

    protected $content;

    protected $defaultParams = array(
        'Status' => Api::STATUS_OK,
        'StatusDetails' => 'Notified successfully',
        'RedirectURL' => null,
    );

    public function __construct(array $params)
    {
        parent::__construct("");
        $this->params = array_filter(
            array_replace(
                $params,
                array_intersect($this->defaultParams, $params)
            )
        );


        $this->setContent();
    }

    protected function setContent()
    {
        if (true == (!isset($this->params['RedirectURL']) || empty($this->params['RedirectURL']))) {
            throw new InvalidArgumentException('The redirection url must be set.');
        }

        $content = '';

        foreach ($this->params as $key => $value) {
            $content = $content . $key . '=' . $value . "\r\n";
        }
        $this->content = $content;

        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }
}
