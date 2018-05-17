<?php
/**
 * Plugin Name: WP l10n Generator
 * Description: Adds CLI commands to generate a variety of translation file formats for WP.
 * Author: Robert O'Rourke
 * Version: 0.1
 */

namespace WP_L10N_Gen;

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

use WP_CLI;

// Load autoloader if composer install run locally.
if ( file_exists( 'vendor/autoload.php' ) {
	require_once 'vendor/autoload.php';
}

// Command classes.
require_once 'inc/class-multi-filter.php';
require_once 'inc/class-wpfunctionsscanner.php';
require_once 'inc/class-wpcode-extractor.php';
require_once 'inc/class-command.php';

WP_CLI::add_command( 'l10n', __NAMESPACE__ . '\Command' );
