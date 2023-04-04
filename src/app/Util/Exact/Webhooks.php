<?php

namespace Zorgportal\Util\Exact;

use \Picqer\Financials\Exact\Connection;
use Picqer\Financials\Exact\WebhookSubscription;
use Picqer\Financials\Exact\Webhook\Authenticatable;

use Zorgportal\Util\Exact\WebhookConsumer\BankEntries;

class Webhooks
{
    const TOPICS = ['BankEntries', 'SalesEntries'];

    const CONSUMERS = [
        'BankEntries.Update' => [ BankEntries::class, 'update' ],
        'BankEntries.Delete' => [ BankEntries::class, 'delete' ],
    ];

    use Authenticatable;

    public static function subscribeToEvents( Connection $client ) : void
    {
        foreach ( self::TOPICS as $topic ) {
            $model = new WebhookSubscription($client);
            $model->CallbackURL = rest_url('zorgportal/v1/exact-online/webhook');
            $model->Topic = $topic;
            $model->Division = $client->getDivision();
            try {
                $model->save();
            } catch(\Exception $e) {
                error_log("exact online webhook subscription ended with an error: {$e->getMessage()} (topic: {$topic})");
            }
        }
    }

    public static function unsubscribeEvents( Connection $client ) : void
    {
        $model = new WebhookSubscription($client);

        $items = $model->getResultSet();

        while ( $items->hasMore() ) {
            foreach ( $items->next() as $subscription ) {
                try {
                    $subscription->delete();
                } catch(\Exception $e) {
                    error_log("exact online webhook subscription.delete ended with an error: {$e->getMessage()}");
                }
            }
        }
    }

    // @todo delete, not in use
    public static function getList( Connection $client ) : array
    {
        $model = new WebhookSubscription($client);

        $items = $model->getResultSet();
        $list = [];

        while ( $items->hasMore() ) {
            foreach ( $items->next() as $subscription ) {
                $list []= $subscription->attributes();
            }
        }

        return $list;
    }

    public static function receive( \WP_REST_Request $request ) : \WP_REST_Response
    {
        $verified = self::authenticate($input=file_get_contents('php://input'), get_option('zorgportal_exact_webhook_secret'));

        if ( ! $verified )
            return new \WP_REST_Response(null, 403);

        error_log(print_r([
            'data' => json_decode($input, true),
        ], 1), 3, '/tmp/webhook.log');

        $consumer = self::CONSUMERS[join('.', [$input['Content']['Topic'], $input['Content']['Action']])] ?? null;

        if ( $consumer && is_callable($consumer) ) {
            $consumer[0] = new $consumer[0]( $input['Content'] );
            call_user_func($consumer, $input['Content']);
        }

        return new \WP_REST_Response(null, 200);
    }

    // @todo rm
    public static function receiveDebug()
    {
        $input = json_decode('{"Content":{"Topic":"BankEntries","ClientId":"2781096d-a179-445f-bbf3-7b7e8bf244ae","Division":3114772,"Action":"Update","Key":"a79d86b2-69c6-4ca7-a537-d9d27afcdaf5","ExactOnlineEndpoint":"https://start.exactonline.nl/api/v1/3114772/financialtransaction/BankEntries(guid\'a79d86b2-69c6-4ca7-a537-d9d27afcdaf5\')","EventCreatedOn":"2023-01-17T22:56:23.087"},"HashCode":"BE53BCB5099EB0CBA782DA424389FA4F733424955DD1931652CFF9ECFE63C9BF"}', true);

        $consumer = self::CONSUMERS[join('.', [$input['Content']['Topic'], $input['Content']['Action']])] ?? null;

        if ( $consumer && is_callable($consumer) ) {
            $consumer[0] = new $consumer[0]( $input['Content'] );
            call_user_func($consumer, $input['Content']);
        }

        return new \WP_REST_Response(null, 200);
    }
}