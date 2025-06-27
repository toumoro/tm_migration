#!/bin/bash

vendor/bin/typo3 tmupgrade:importsql -d migration/before-updateschema
# vendor/bin/typo3 tmupgrade:importsql -f migration/before-updateschema/script.sql
vendor/bin/typo3 database:updateschema "*.add,*.change"
vendor/bin/typo3 tmupgrade:importsql -d migration/after-updateschema
# vendor/bin/typo3 tmupgrade:importsql -f migration/after-updateschema/script.sql
vendor/bin/typo3 tmupgrade:run -n
composer install -n