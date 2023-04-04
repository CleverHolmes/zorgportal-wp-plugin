<?php
/*
Plugin Name: Zorgportal
Plugin URI: https://zorg-portal.nl
Description: Zorgportal Project
Author: ZorgPortal
Version: 0.2.5
Author URI: https://zorg-portal.nl
Text Domain: zorgportal
*/

if ( ! defined ( 'WPINC' ) ) {
    exit; // direct access
}

require_once __DIR__ . '/src/vendor/autoload.php';

(new \Zorgportal\App(__FILE__))->setup();
