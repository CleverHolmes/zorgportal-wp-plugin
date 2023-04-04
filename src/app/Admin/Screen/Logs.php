<?php

namespace Zorgportal\Admin\Screen;

use Zorgportal\App;
use Zorgportal\EoLogs;

class Logs extends Screen
{
    public function render()
    {
        return $this->renderTemplate('logs.php', array_merge(EoLogs::query(array_merge([], [
            'current_page' => (int) ($_GET['p'] ?? ''),
            'search' => $_GET['search'] ?? '',
            'orderby' => $this->getActiveSort()['prop'] ?? '',
            'order' => $this->getActiveSort()['order'] ?? '',
        ],
        isset($_GET['order_id']) ? ['order_id' => intval($_GET['order_id'])] : [],
        isset($_GET['id']) ? ['id' => intval($_GET['id'])] : [] )), [
            'getActiveSort' => [ $this, 'getActiveSort' ],
            'getNextSort' => [ $this, 'getNextSort' ],
            'nonce' => wp_create_nonce('zorgportal'),
        ]));
    }

    public function scripts()
    {
        $base = trailingslashit(plugin_dir_url( $this->appContext->getPluginFile() ));
        wp_enqueue_style( 'euproj-logs', "{$base}src/assets/logs.css", [], $this->appContext::SCRIPTS_VERSION );
        wp_enqueue_script( 'euproj-logs', "{$base}src/assets/js/logs.js", ['jquery'], $this->appContext::SCRIPTS_VERSION, 1 );
    }

    public function update()
    {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'zorgportal' ) )
            return $this->error( __('Invalid request, authorization check failed. Please try again.', 'zorgportal') );

        $logs = array_filter(array_unique( array_map('intval', (array) ($_POST['logs'] ?? '')) ));

        if ( ! $logs )
            return;

        if ( 'delete' == ( $_POST['action2'] ?? '' ) ) {
            $del = EoLogs::delete($logs);
            return $this->success( sprintf(
                _n( '%d log deleted.', '%d logs deleted.', $del, 'zorgportal' ), $del
            ) );
        }
    }

    public function getActiveSort() : array
    {
        $sort = explode(',', (string) ( $_GET['sort'] ?? '' ));
        $prop = strtolower($sort[0] ?? '');
        $order = strtolower($sort[1] ?? '');

        if ( $prop && ! array_key_exists($prop, EoLogs::COLUMNS) ) {
            $prop = '';
            $order = '';
        }

        $order = in_array($order, ['asc','desc']) ? $order : 'desc';
        $order = $prop ? $order : '';

        return compact('order', 'prop');
    }

    public function getNextSort( string $prop ) : string
    {
        $current = $this->getActiveSort();

        if ( $prop == $current['prop'] ) {
            return 'asc' !== $current['order'] ? 'asc' : 'desc';
        }

        return 'desc';
    }
}