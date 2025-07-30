#!/bin/bash

echo "========================================"
echo ">>> Step 1: Install tm-migration package"
echo "========================================"
composer require toumoro/tm-migration
composer update toumoro/tm-migration

echo "========================================================"
echo ">>> Step 2: Importing SQL scripts before updating schema"
echo "========================================================"
vendor/bin/typo3 tmupgrade:importsql -d migration/before-updateschema
# vendor/bin/typo3 tmupgrade:importsql -f migration/before-updateschema/script.sql

echo "===================================="
echo ">>> Step 3: Updating database schema"
echo "===================================="
vendor/bin/typo3 database:updateschema "*.add,*.change"

echo "========================================================"
echo ">>> Step 4: Importing SQL scripts after updating schema"
echo "========================================================"
vendor/bin/typo3 tmupgrade:importsql -d migration/after-updateschema
# vendor/bin/typo3 tmupgrade:importsql -f migration/after-updateschema/script.sql

echo "========================================="
echo ">>> Step 5: Running TYPO3 upgrade wizards"
echo "========================================="
vendor/bin/typo3 tmupgrade:run -n
# vendor/bin/typo3 tmupgrade:run container_containerMigrateSorting

echo "====================================="
echo ">>> Step 6: Flushing all TYPO3 caches"
echo "====================================="
vendor/bin/typo3 cache:flush

echo "======================================="
echo ">>> Step 7: Remove tm-migration package"
echo "======================================="
composer remove toumoro/tm-migration