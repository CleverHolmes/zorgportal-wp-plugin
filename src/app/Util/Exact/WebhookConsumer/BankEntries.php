<?php

namespace Zorgportal\Util\Exact\WebhookConsumer;

use Picqer\Financials\Exact\BankEntry;
use Picqer\Financials\Exact\BankEntryLine;
use \Zorgportal\Util\Exact\Client;

class BankEntries extends WebhookConsumer
{
    public function update()
    {
        $lines = new BankEntryLine( $client = Client::connect() );

        $lines = $lines->filterAsGenerator("EntryID eq guid'{$this->data['Key']}'", '', '*');

        foreach ( $lines as $line ) {
            var_dump( $line->attributes() );
        }
    }
}