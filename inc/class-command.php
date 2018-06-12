<?php
/**
 * WP CLI command to generate and convert translation files.
 *
 * @TODO: add tests
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
	 * : The text domain to extract strings for. Prepended to translation files.
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
	 *   eg. WP_CONTENT_DIR . '/languages/plugins/'
	 *
	 * [--verbose]
	 * : Verbose logging output.
	 *
	 * @TODO : add CLI support for constants via WPCode::$options['constants']
	 * @TODO : make exclude arg work
	 * @TODO : make array / multi args work better eg. via other CLI input, grep, awk etc
	 *
	 * @when after_wp_config_load
	 */
	public function generate( $args, $assoc_args = [] ) {

		$extract_from = WP_CONTENT_DIR;
		$extract_to   = WP_CONTENT_DIR . '/languages/plugins';

		$assoc_args = array_merge( [
			'type'         => 'po',
			'locale'       => 'en_US',
			'locales'      => 'en_US',
			'domain'       => 'default',
			'exclude'      => 'vendor,node_modules',
			'extract-from' => $extract_from,
			'extract-to'   => $extract_to,
		], $assoc_args );

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

			foreach ( $php_files as $file_path => $file_info ) {
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
			$path = rtrim( $assoc_args['extract-to'], DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . $assoc_args['domain'] . '-' . $locale;

			// Allow generating multiple types at a time.
			$types = explode( ',', $assoc_args['type'] );

			foreach ( $types as $type ) {
				$this->to( $translations, $type, $path );
			}
		}
	}

	/**
	 * Convert translation files from one format to another.
	 *
	 * <file>
	 * : The file or directory of files to convert, makes a best guess as to
	 *   the type based on the file name.
	 *   When <file> is a directory --input-type is required.
	 *
	 * <to-type>
	 * : The type of file to convert to.
	 * ---
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
	 * [--input-type=<string>]
	 * : Optionally specify the input file type. Required if <file> is a directory.
	 *
	 * [--pattern=<string>]
	 * : An optional regular expression to use with a directory to narrow down which
	 *   files are converted. Use this to specify a textdomain for example.
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function convert( $args, $assoc_args = [] ) {

		// Get positional args.
		list( $file, $to_type ) = $args;

		$input_type = false;

		if ( ! is_dir( $file ) ) {
			// Get input type.
			$input_type = pathinfo( $file, PATHINFO_EXTENSION );
		}

		// Allow specifying input type for dict types.
		if ( isset( $assoc_args['input-type'] ) ) {
			$input_type = $assoc_args['input-type'];
		}

		// Check we know our source type.
		if ( ! $input_type ) {
			WP_CLI::error( '--input-type argument is missing!' );

			return;
		}

		// Normalise file extension for regex.
		$file_extension = str_replace( [ 'dict', 'yaml' ], [ '', 'yml' ], $input_type );

		// Files iterator.
		try {
			// Handle directories.
			if ( is_dir( $file ) ) {
				$files_iterator = new \FilesystemIterator( $file );

				// Filter results.
				if ( isset( $assoc_args['pattern'] ) ) {
					$files_by_type = new MultiFilter( $files_iterator, [
						"/\.{$file_extension}$/i",
						$assoc_args['pattern'],
					] );
				} else {
					$files_by_type = new MultiFilter( $files_iterator, "/\.{$file_extension}$/i" );
				}
			} else {
				$files_by_type = [
					$file => $file,
				];
			}

			WP_CLI::line( sprintf( 'Converting: %s -> %s', $input_type, $to_type ) );

			foreach ( $files_by_type as $file_path => $file_info ) {
				$translations = $this->from( $file_path, $input_type );

				if ( ! $translations ) {
					WP_CLI::error( 'Could not get translations, you may need to specify the --input-type' );
					continue;
				}

				$this->to( $translations, $to_type, $file_path );
			}
		} catch ( \Exception $exception ) {
			WP_CLI::error( 'There was an error: ' . $exception->getMessage() );
		}
	}

	/**
	 * Shortcut for po to mo conversion.
	 *
	 * <file>
	 * : .po file to convert or directory containing .po files.
	 *
	 * [--pattern=<string>]
	 * : An optional regular expression to use with a directory to narrow down which
	 *   files are converted.
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function po2mo( $args, $assoc_args ) {
		$args[]                   = 'mo';
		$assoc_args['input-type'] = 'po';
		$this->convert( $args, $assoc_args );
	}

	/**
	 * Get Translations object from a file.
	 *
	 * @param string $file
	 * @param string $type
	 * @return false|Translations
	 */
	protected function from( string $file, string $type = '' ) {
		$translations = false;

		// Determine type from extension.
		if ( empty( $type ) ) {
			$type = pathinfo( $file, PATHINFO_EXTENSION );
		}

		switch ( $type ) {
			case 'po':
				$translations = Translations::fromPoFile( $file );
				break;
			case 'mo':
				$translations = Translations::fromMoFile( $file );
				break;
			case 'php':
				$translations = Translations::fromPhpArrayFile( $file );
				break;
			case 'csv':
				$translations = Translations::fromCsvFile( $file );
				break;
			case 'csvdict':
				$translations = Translations::fromCsvDictionaryFile( $file );
				break;
			case 'json':
				$translations = Translations::fromJsonFile( $file );
				break;
			case 'jsondict':
				$translations = Translations::fromJsonDictionaryFile( $file );
				break;
			case 'jed':
				$translations = Translations::fromJedFile( $file );
				break;
			case 'xliff':
				$translations = Translations::fromXliffFile( $file );
				break;
			case 'yml':
			case 'yaml':
				$translations = Translations::fromYamlFile( $file );
				break;
			case 'yamldict':
				$translations = Translations::fromYamlDictionaryFile( $file );
				break;
		}

		return $translations;
	}

	/**
	 * Save translations object to a type.
	 *
	 * @param \Gettext\Translations $translations
	 * @param string                $type
	 * @param string                $file
	 * @param array                 $file_args
	 * @return \Gettext\Translations
	 */
	protected function to( Translations $translations, string $type, string $file, $file_args = [
		'includeHeaders' => true,
	] ) {
		// Remove extension.
		$file = str_replace( '.' . pathinfo( $file, PATHINFO_EXTENSION ), '', $file );

		switch ( $type ) {
			case 'po':
				$this->merge( $translations, $type, "{$file}.po" );
				$translations->toPoFile( "{$file}.po", $file_args );
				break;
			case 'mo':
				$this->merge( $translations, $type, "{$file}.mo" );
				$translations->toMoFile( "{$file}.mo", $file_args );
				break;
			case 'php':
				$this->merge( $translations, $type, "{$file}.php" );
				$translations->toPhpArrayFile( "{$file}.php", $file_args );
				break;
			case 'csv':
				$this->merge( $translations, $type, "{$file}.csv" );
				$translations->toCsvFile( "{$file}.csv", $file_args );
				break;
			case 'csvdict':
				$this->merge( $translations, $type, "{$file}.csv" );
				$translations->toCsvDictionaryFile( "{$file}.csv", $file_args );
				$type = 'csv';
				break;
			case 'json':
				$this->merge( $translations, $type, "{$file}.json" );
				$translations->toJsonFile( "{$file}.json", $file_args );
				break;
			case 'jsondict':
				$this->merge( $translations, $type, "{$file}.json" );
				$translations->toJsonDictionaryFile( "{$file}.json", $file_args );
				$type = 'json';
				break;
			case 'jed':
				$this->merge( $translations, $type, "{$file}.json" );
				$translations->toJedFile( "{$file}.json", $file_args );
				$type = 'json';
				break;
			case 'xliff':
				$this->merge( $translations, $type, "{$file}.xliff" );
				$translations->toXliffFile( "{$file}.xliff", $file_args );
				break;
			case 'yaml':
				$this->merge( $translations, $type, "{$file}.yml" );
				$translations->toYamlFile( "{$file}.yml", $file_args );
				$type = 'yml';
				break;
			case 'yamldict':
				$this->merge( $translations, $type, "{$file}.yml" );
				$translations->toYamlDictionaryFile( "{$file}.yml", $file_args );
				$type = 'yml';
				break;
		}

		WP_CLI::success( sprintf(
			'Saved: %s',
			"{$file}.{$type}"
		) );

		return $translations;
	}

	/**
	 * Merge an existing file with the passed Translations object.
	 *
	 * @param Translations $translations
	 * @param string $type
	 * @param string $file
	 */
	protected function merge( Translations $translations, $type, $file ) {
		if ( ! file_exists( $file ) ) {
			return;
		}

		$existing = $this->from( $file, $type );
		$translations->mergeWith( $existing );
	}

}
