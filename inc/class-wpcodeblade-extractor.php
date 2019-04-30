<?php

namespace Gettext\Extractors;

use Gettext\Extractors\WPCode;
use Gettext\Translations;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

class WPCodeBlade extends WPCode implements ExtractorInterface {

	public static function fromString( $string, Translations $translations, array $options = [] ) {
		
		if (empty($options['facade'])) {
			$cachePath = empty($options['cachePath']) ? sys_get_temp_dir() : $options['cachePath'];
			$bladeCompiler = new BladeCompiler(new Filesystem(), $cachePath);
			$string = $bladeCompiler->compileString($string);
		} else {
			$string = $options['facade']::compileString($string);
		}

		parent::fromString($string, $translations, $options);
	}

}