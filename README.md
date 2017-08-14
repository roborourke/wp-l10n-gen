Modern Translation File Generator for WordPress
===============================================

Previously WordPress developers needed to use `makepot.php` to generate .po files
necessary for translation.

This updated WP CLI command allows you to generate files 


[--type=<string>]
: The output file type.
---
default: po
options:
  - csv
  - csvdict
  - json
  - jsondict
  - mo
  - php
  - po
  - jed
  - xliff
  - yaml
  - yamldict
---

[--locale=<string>]
: The language the original strings are in.
---
default: en_US
---
*
[--locales=<array>]
: A list of comma separated locale codes to generate translation ready files for.
  Alternatively can be a text file containing locales on separate lines.
---
default: en_US
---
*
[--domain=<string>]
: The text domain to extract strings for.
---
default: 'default'
---
*
[--extract-from=<string>]
: The path to extract from, defaults to the entire wp-content directory.
  Defaults to WP_CONTENT_DIR
*
[--extract-to=<string>]
: The full or relative path to a directory to save files to.
  Defaults to a directory in languages folder named after the textdomain.
  eg. WP_CONTENT_DIR . '/languages/default/'
*
[--verbose]
: Verbose logging output.
