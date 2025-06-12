# tm_migration
TYPO3 extension that brings together the tools used for a major TYPO3 migration.

## Features
### Dependencies :
The extension relies on <code>typo3-fractor, typo3-rector, and core-upgrader</code> as base dependencies.

### CLI Commands :
An export CLI command that allows developers to export CTypes and List Types to JSON or CSV file format :

<code>vendor/bin/typo3 export:types -t [FILE_TYPE] -m [FILE_NAME]</code>

e.x: <code>vendor/bin/typo3 export:types -t csv -m types.csv</code>

<hr />

A CLI command that fixes the duplicate entries in _mm relation tables, e.g: <code>sys_category_record_mm</code> :

<code>vendor/bin/typo3 upgrade:fixdatabaseerrors</code>

### Upgrade Wizards :
An upgrade wizard facilitates the migration of <b>list_type</b> plugins to <b>CType</b> content elements using a configurable array mapping.

The mapping configuration can be customized in the <b>tm_migration</b> extension settings.

<hr />

An upgrade wizard that truncates or deletes entries from Log table if number of days is set in <b>tm_migration</b> extension settings.

<hr />

An upgrade wizard that migrates <b>grid elements</b> to <b>container</b>