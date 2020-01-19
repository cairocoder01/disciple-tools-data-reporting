[![Build Status](https://travis-ci.com/DiscipleTools/disciple-tools-export-plugin-template.svg?branch=master)](https://travis-ci.com/DiscipleTools/disciple-tools-export-plugin-template)

# Disciple Tools Export Plugin
The Disciple Tools Export Plugin is intended to accelerate integrations and extensions to the Disciple Tools system.
This basic plugin export has some of the basic elements to quickly launch and extension project in the pattern of
the Disciple Tools system.


### The export plugin is equipped with:
1. Wordpress style requirements
1. Travis Continueous Integration
1. Disciple Tools Theme presence check
1. Remote upgrade system for ongoing updates outside the Wordpress Directory
1. Multilingual ready
1. PHP Code Sniffer support (composer) @use /vendor/bin/phpcs and /vendor/bin/phpcbf
1. Export Admin menu and options page with tabs.

### Refactoring this plugin as your own:
1. Refactor all occurences of the name `Export_Plugin`, `export_plugin`, `export-plugin`, and `Export Plugin` with you're own plugin
name for the `disciple-tools-export-plugin.php and admin-menu-and-tabs.php files.
1. Update the README.md and LICENSE
1. Update the translation strings inside `default.pot` file with a multilingual sofware like POEdit, if you intend to make your plugin multilingual.
