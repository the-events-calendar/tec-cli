<?php
/**
 * Autoloader for classes in the "Tribe__Namespace__Format"
 */
include dirname( __FILE__ ) . '/src/Autoloader.php';

$autoloader = new Tribe__CLI__Autoloader();
$autoloader->register_class( 'Tribe__CLI__Main', dirname( __FILE__ ) . '/src/Main.php' );
$autoloader->register_prefix( 'Tribe__CLI__', dirname( __FILE__ ) . '/src' );
$autoloader->register_prefix( 'Tribe__Tickets_Plus__', dirname( dirname( __FILE__ ) ) . '/event-tickets-plus/src/tribe' );

spl_autoload_register( array( $autoloader, 'load_class' ) );
