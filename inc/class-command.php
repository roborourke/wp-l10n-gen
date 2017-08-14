<?php
/**
 * WP CLI command to generate files.
 *
 * @TODO: add transform command, one file type to another.
 * @TODO: add po2mo shortcut
 */

namespace WP_L10N_Gen;

use WP_CLI;
use WP_CLI_Command;
use function WP_CLI\Utils\make_progress_bar;
use Gettext\Translations;

class Command extends WP_CLI_Command {

	/**
	 * Generate translation files.
	 *
	 * [--type=<string>]
	 * : The output file type.
	 * ---
	 * default: po
	 * options:
	 *   - csv
	 *   - csvdict
	 *   - json
	 *   - jsondict
	 *   - mo
	 *   - php
	 *   - po
	 *   - jed
	 *   - xliff
	 *   - yaml
	 *   - yamldict
	 * ---
	 *
	 * [--locale=<string>]
	 * : The language the original strings are in.
	 * ---
	 * default: en_US
	 * ---
	 *
	 * [--locales=<array>]
	 * : A list of comma separated locale codes to generate translation ready files for.
	 *   Alternatively can be a text file containing locales on separate lines.
	 * ---
	 * default: en_US
	 * ---
	 *
	 * [--domain=<string>]
	 * : The text domain to extract strings for.
	 * ---
	 * default: 'default'
	 * ---
	 *
	 * [--extract-from=<string>]
	 * : The path to extract from, defaults to the entire wp-content directory.
	 *   Defaults to WP_CONTENT_DIR
	 *
	 * [--extract-to=<string>]
	 * : The full or relative path to a directory to save files to.
	 *   Defaults to a directory in languages folder named after the textdomain.
	 *   eg. WP_CONTENT_DIR . '/languages/default/'
	 *
	 * [--verbose]
	 * : Verbose logging output.
	 *
	 * @TODO: add CLI support for constants via WPCode::$options['constants']
	 * @TODO: make exclude arg work
	 * @TODO: make array / multi args work better eg. via other CLI input, grep, awk etc
	 *
	 * @when after_wp_config_load
	 */
	public function generate( $args, $assoc_args = [] ) {

		$extract_from = WP_CONTENT_DIR;
		$extract_to   = WP_CONTENT_DIR . '/languages/__domain__';

		$assoc_args = wp_parse_args( $assoc_args, [
			'type'         => 'po',
			'locale'       => 'en_US',
			'locales'      => 'en_US',
			'domain'       => 'default',
			'exclude'      => 'vendor,node_modules',
			'extract-from' => $extract_from,
			'extract-to'   => $extract_to,
		] );

		// If extract-to is default then swap out domain.
		if ( $extract_to === $assoc_args['extract-to'] ) {
			$assoc_args['extract-to'] = str_replace( '__domain__', $assoc_args['domain'], $assoc_args['extract-to'] );
		}

		// Create initial result set to merge into.
		$translations = new Translations();
		$translations->setLanguage( $assoc_args['locale'] );
		$translations->setDomain( $assoc_args['domain'] );

		// Get all paths to generate translation files for.
		$paths = array_map( 'trim', explode( ',', $assoc_args['extract-from'] ) );

		WP_CLI::log( 'Preparing to parse files...' );

		foreach ( $paths as $path ) {
			$directory = new \RecursiveDirectoryIterator( $path );
			$iterator  = new \RecursiveIteratorIterator( $directory );
			$php_files = new \RegexIterator( $iterator, '/^.+\.php\d?$/i', \RecursiveRegexIterator::GET_MATCH );

			// Show progress.
			$progress = make_progress_bar( sprintf( 'Extracting strings from %s', $path ), iterator_count( $php_files ) );

			foreach ( $php_files as $file_path => $file ) {
				if ( isset( $assoc_args['verbose'] ) ) {
					WP_CLI::log( sprintf( 'Extracting strings from %s', $file_path ) );
				}
				$translations->addFromWPCodeFile( $file_path );
				$progress->tick();
			}

			$progress->finish();
		}

		// Try to create target directory if it doesn't exist.
		if ( ! file_exists( $assoc_args['extract-to'] ) ) {
			mkdir( $assoc_args['extract-to'], 0755, true );
		}

		// Add default language to locales.
		$locales = array_map( 'trim', explode( ',', $assoc_args['locales'] ) );
		$locales = array_merge( $locales, (array) $assoc_args['locale'] );
		$locales = array_unique( $locales );

		foreach ( $locales as $locale ) {

			// Update language header.
			$translations->setLanguage( $locale );

			// Get the file name.
			$save_to = "{$assoc_args['extract-to']}/{$locale}";

			// Allow generating multiple types at a time.
			$types = explode( ',', $assoc_args['type'] );

			// File gen args.
			$file_args = [
				'includeHeaders' => true,
			];

			foreach ( $types as $type ) {
				switch ( $type ) {
					case 'po':
						$translations->toPoFile( "{$save_to}.po", $file_args );
						break;
					case 'mo':
						$translations->toMoFile( "{$save_to}.mo", $file_args );
						break;
					case 'php':
						$translations->toPhpArrayFile( "{$save_to}.php", $file_args );
						break;
					case 'csv':
						$translations->toCsvFile( "{$save_to}.csv", $file_args );
						break;
					case 'csvdict':
						$translations->toCsvDictionaryFile( "{$save_to}.csv", $file_args );
						break;
					case 'json':
						$translations->toJsonFile( "{$save_to}.json", $file_args );
						break;
					case 'jsondict':
						$translations->toJsonDictionaryFile( "{$save_to}.json", $file_args );
						break;
					case 'jed':
						$translations->toJedFile( "{$save_to}.jed", $file_args );
						break;
					case 'xliff':
						$translations->toXliffFile( "{$save_to}.xliff", $file_args );
						break;
					case 'yaml':
						$translations->toYamlFile( "{$save_to}.yml", $file_args );
						break;
					case 'yamldict':
						$translations->toYamlDictionaryFile( "{$save_to}.yml", $file_args );
						break;
				}

				WP_CLI::success( sprintf(
					'Saved %s file for %s: %s',
					$type,
					$locale,
					str_replace( [ 'dict', 'yaml' ], [ '', 'yml' ], "{$save_to}.{$type}" )
				) );
			}
		}
	}

}
