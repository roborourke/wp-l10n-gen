<?php

namespace Gettext\Utils;

use Exception;
use Gettext\Translations;

class WPJsFunctionsScanner extends JsFunctionsScanner {

	/**
	 * Search for specific functions and create translations.
	 *
	 * @param Translations $translations The translations instance where save the values
	 * @param array        $options      The extractor options
	 * @throws Exception
	 */
	public function saveGettextFunctions( Translations $translations, array $options ) {
		$functions = $options['functions'];
		$file      = $options['file'];

		foreach ( $this->getFunctions( $options['constants'] ) as $function ) {
			list( $name, $line, $args ) = $function;

			if ( ! isset( $functions[ $name ] ) ) {
				continue;
			}

			$context = $original = $plural = null;
			$domain  = 'default';

			switch ( $functions[ $name ] ) {
				case 'noop':
					if ( ! isset( $args[0] ) ) {
						continue 2;
					}

					$original = $args[0];
					break;

				case 'nnoop':
					if ( ! isset( $args[1] ) ) {
						continue 2;
					}

					list( $original, $plural ) = $args;
					break;

				case 'dgettext':
					if ( ! isset( $args[1] ) ) {
						continue 2;
					}

					list( $original, $domain ) = $args;
					break;

				case 'dpgettext':
					if ( ! isset( $args[2] ) ) {
						continue 2;
					}

					list( $original, $context, $domain ) = $args;
					break;

				case 'dnpgettext':
					if ( ! isset( $args[3] ) ) {
						continue 2;
					}

					list( $original, $plural, $context, $domain ) = $args;
					break;

				case 'dngettext':
					if ( ! isset( $args[2] ) ) {
						continue 2;
					}

					list( $original, $plural, $domain ) = $args;
					break;

				default:
					throw new Exception( sprintf( 'Not valid function %s', $functions[ $name ] ) );
			}

			if ( (string) $original !== '' && ( $domain === null || $domain === $translations->getDomain() ) ) {
				$translation = $translations->insert( $context, $original, $plural );
				$translation->addReference( $file, $line );

				if ( isset( $function[3] ) ) {
					foreach ( $function[3] as $extractedComment ) {
						$translation->addExtractedComment( $extractedComment );
					}
				}
			}
		}
	}

}
