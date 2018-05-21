<?php

namespace Gettext\Extractors;

use Gettext\Translations;
use Gettext\Utils\WPJsFunctionsScanner;

/**
 * Class to get gettext strings from php files returning arrays.
 */
class WPJsCode extends WPCode {

	/**
	 * {@inheritdoc}
	 */
	public static function fromString( $string, Translations $translations, array $options = [] ) {

		$options += static::$options;

		$functions = new WPJsFunctionsScanner( $string );

		if ( $options['extractComments'] !== false ) {
			$functions->enableCommentsExtraction( $options['extractComments'] );
		}

		$functions->saveGettextFunctions( $translations, $options );
	}

}
