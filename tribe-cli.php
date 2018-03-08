<?php
/*
Plugin Name: Tribe CLI
Description: A collection of WP-CLI utilities for testing and maintenance purposes.
Version: 0.1
Author: Modern Tribe, Inc.
Author URI: http://m.tri.be/21
Text Domain: tribe-cli
License: GPLv2 or later
*/

/*
Copyright 2017 by Modern Tribe Inc and the contributors

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

define( 'TRIBE_CLI_DIR', dirname( __FILE__ ) );
define( 'TRIBE_CLI_FILE', __FILE__ );

// Composer managed PHP 5.2 compatible autoloader, will include our autoloader too
if ( version_compare( PHP_VERSION, '5.3.0', ">=" ) ) {
	include dirname( __FILE__ ) . '/vendor/autoload.php';
} else {
	include dirname( __FILE__ ) . '/vendor/autoload_52.php';
}

function tribe_cli_init() {
	$container = new tad_DI52_Container();

	$container->register( 'Tribe__Cli__Main' );
	$container->register( 'Tribe__Cli__Service_Providers__Events' );
	$container->register( 'Tribe__Cli__Service_Providers__Tickets' );
	$container->register( 'Tribe__Cli__Service_Providers__Tribe_Commerce' );
	$container->register( 'Tribe__Cli__Service_Providers__Tickets_Plus' );
	$container->register( 'Tribe__Cli__Service_Providers__Utils' );
}

include_once(dirname(__FILE__) . '/src/functions/template.php');

add_action( 'plugins_loaded', 'tribe_cli_init' );