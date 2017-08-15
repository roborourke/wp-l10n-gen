Modern Translation File Generator for WordPress
===============================================

Previously WordPress developers needed to use `makepot.php` to generate .po files
necessary for translation.

This updated WP CLI command allows you to generate different types of translation file from WP code.

## Installation

Currently you'll need to install and activate the plugin.

In your plugins directory do the following:

```bash
git clone git@github.com:roborourke/wp-l10n-gen.git 
cd wp-l10-gen
composer install
wp plugin activate wp-l10n-gen # (or activate via wp-admin)
```

Or using composer:

```bash
composer require roborourke/wp-l10n-gen
```

## Usage

More docs to come soon, to see options for now run:

```bash
wp l10n generate --help
wp l10n convert --help
wp l10n po2mo --help
```

## Roadmap 

 * Improve generated headers
 * ~Add conversions between any translation file types~
 * ~Add po2mo shortcut command~
 * Make it work as a WP CLI package
 * Documentation for use with JS based projects?

## About

Being frustrated with the existing tools for generating translation files I wondered if
there was a better way that more closely tied in with the modern ways we interact with
WP via the command line now.

Looking to other PHP Projects and how they manage translations was a useful exercise although
WP's translation function don't follow the usual standards. A bit of hacking later and I was
able to get Oscar Otero's excellent [Gettext](https://github.com/oscarotero/Gettext) library
working with WordPress code.

## Contributing

It's very early days yet but if anyone finds this useful and wants to contribute please go
ahead. You'll find my outline for a roadmap above and plenty of `TODO` comments in the code.

### License

GPLv3+
