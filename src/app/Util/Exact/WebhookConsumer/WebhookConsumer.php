<?php

namespace Zorgportal\Util\Exact\WebhookConsumer;

abstract class WebhookConsumer
{
    protected $data;

    public function __construct( array $data )
    {
        $this->data = $data;
    }
}