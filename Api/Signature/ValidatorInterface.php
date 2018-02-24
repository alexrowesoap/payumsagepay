<?php

namespace Alexrowesoap\Payumsagepay\Api\Signature;

interface ValidatorInterface
{
    public function setParams(array $params);

    public function tamperingDetected($recievedSignature);
}
