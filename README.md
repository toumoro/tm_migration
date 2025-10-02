[![Latest Stable Version](https://poser.pugx.org/toumoro/tm-migration/v/stable)](https://packagist.org/packages/toumoro/tm-migration)
[![Total Downloads](https://poser.pugx.org/toumoro/tm-migration/downloads)](https://packagist.org/packages/toumoro/tm-migration)
[![License](https://poser.pugx.org/toumoro/tm-migration/license)](https://packagist.org/packages/toumoro/tm-migration)

FR
---

# Extension tm_migration

> Une extension TYPO3 qui regroupe des outils essentiels pour faciliter les migrations majeures de TYPO3.

---

## Dépendances

| Package         | Version                           | Compatibilité      |
|-----------------|-----------------------------------|--------------------|
| `typo3-fractor` | v0.5.1                            | 12.4.0 & 13.4.0    |
| `typo3-rector`  | 2.14.4 & v3.5.0                   | 12.4.0 & 13.4.0    |
| `core-upgrader` | dev-release/v12 & dev-release/v13 | 12.4.0 & 13.4.0    |

---

## Étapes

**1. Installer l’extension via composer :**

```bash
composer require toumoro/tm-migration --dev
```

**2. Mettre à jour les dépendances Composer vers les dernières versions :**
```bash
composer require $(composer show -s --format=json | jq '.requires | keys | map(.+" ") | add' -r)
composer require --dev $(composer show -s --format=json | jq '.devRequires | keys | map(.+" ") | add' -r)
```

**3. Configurer l’extension dans `config/system/settings.php` :**

```php
'tm_migration' => [
    'cTypeToListTypeMappingArray' => 'pi_plugin1:new_content_element1',
    'disableTrucateLogUpgradeWizard' => '1',
    'numberOfDays' => '180',
    'upgradeWizards' => [
        'exlcuded' => 'fillTranslationSourceField',
        'fromVersion' => '9.5'
    ],
],
```

**4. Copier le dossier de migration dans le répertoire racine de votre projet :**

```bash
chmod +x vendor/toumoro/tm-migration/services/configure.sh
vendor/toumoro/tm-migration/services/configure.sh
```

**5. Exporter les valeurs `CType` et `list_type` en JSON ou CSV (optionnel) :**

- Ces commandes d'export de CType/ListType préparent à l'éxécution des Ugrade Wizards.
- Permet de générer le mapping de ListType vers CType afin de les préciser dans setting.php (tm_migration[cTypeToListTypeMappingArray]).

```bash
vendor/bin/typo3 tmexport:types -t [TYPE_FICHIER] -m [NOM_FICHIER]
```

Exemple pour CSV :

```bash
vendor/bin/typo3 tmexport:types -t csv -m types.csv
```

Exemple pour JSON :

```bash
vendor/bin/typo3 tmexport:types -t json
```

**6. Corriger les relations MM en double (uniquement si nécessaire) :**

  - 6.0. Exécuter la commande de correction des doublons MM :
    ```bash
    vendor/bin/typo3 tmupgrade:fixdatabaseerrors
    ```
  - 6.1. Exécuter la commande de update schema :
    ```bash
    vendor/bin/typo3 database:updateschema "*.add,*.change"
    ```

**7. Exécuter les Upgrade Wizards :**

```bash
vendor/bin/typo3 tmupgrade:run
```

**8. Copier les fichiers de configuration Rector & Fractor dans votre projet :**

```bash
cp vendor/toumoro/tm-migration/Resources/Private/Config/Rector/rector_v13.php .
cp vendor/toumoro/tm-migration/Resources/Private/Config/Fractor/fractor_v13.php .
```

**9. Exécuter Rector & Fractor en mode simulation (dry-run) :**

```bash
vendor/bin/rector process --debug --dry-run 2>&1 | tee rector-dryrun.txt
vendor/bin/fractor process --dry-run 2>&1 | tee fractor-dryrun.txt
```

**10. Appliquer les correctifs de Rector & Fractor :**

```bash
vendor/bin/rector process
vendor/bin/fractor process
```

> **Astuce :**
> Utiliser `--debug` avec Rector évite les problèmes liés au traitement en parallèle.

**11. Importer les fichiers SQL (avant et après la mise à jour du schéma de base de données) :**

```bash
vendor/bin/typo3 tmupgrade:importsql -f [NOM_FICHIER]
vendor/bin/typo3 tmupgrade:importsql -d [REPERTOIRE]
```

Exemple :
```bash
vendor/bin/typo3 tmupgrade:importsql -f migration.sql
vendor/bin/typo3 tmupgrade:importsql -d before-updateschema
```

**12. Séparer les entrées d’historique de `sys_log` (uniquement si vous migrez un site depuis une version TYPO3 < 9.5) :**

```bash
vendor/bin/typo3 tmupgrade:seperate-syshistory-from-syslog -d [JOURS] -l [LIMITE]
```

---

## Script pour exécuter toutes les étapes :

- Utilisez ce script pour re-simuler les étapes de migration ou pour le jour du déploiement en production.

```bash
chmod +x migration/run.sh
migration/run.sh
```

EN
---

# tm_migration Extension

> A TYPO3 extension that bundles essential tools to streamline major TYPO3 migrations.

---

## Dependencies

| Package         | Version                           | Compatibility     |
|-----------------|-----------------------------------|-------------------|
| `typo3-fractor` | v0.5.1                            | 12.4.0 & 13.4.0   |
| `typo3-rector`  | 2.14.4 & v3.5.0                   | 12.4.0 & 13.4.0   |
| `core-upgrader` | dev-release/v12 & dev-release/v13 | 12.4.0 & 13.4.0   |

---

## Steps

**1. Install the extension via composer:**

```bash
composer require toumoro/tm-migration --dev
```

**2. Update Composer dependencies to the latest versions:**

```bash
composer require $(composer show -s --format=json | jq '.requires | keys | map(.+" ") | add' -r)
composer require --dev $(composer show -s --format=json | jq '.devRequires | keys | map(.+" ") | add' -r)
```

**3. Configure the extension in `config/system/settings.php`:**

```php
'tm_migration' => [
    'cTypeToListTypeMappingArray' => 'pi_plugin1:new_content_element1',
    'disableTrucateLogUpgradeWizard' => '1',
    'numberOfDays' => '180',
    'upgradeWizards' => [
        'exlcuded' => 'fillTranslationSourceField',
        'fromVersion' => '9.5'
    ],
],
```

**4. Copy the migration folder to the base directory of your project:**

```bash
chmod +x vendor/toumoro/tm-migration/services/configure.sh
vendor/toumoro/tm-migration/services/configure.sh
```

**5. Export `CType` and `list_type` values to JSON or CSV (optional):**

- These CType/ListType export commands prepare for the execution of Upgrade Wizards.
- Allows you to generate the mapping from ListType to CType in order to specify them in setting.php (tm_migration[cTypeToListTypeMappingArray]).

```bash
vendor/bin/typo3 tmexport:types -t [FILE_TYPE] -m [FILE_NAME]
```
Example for CSV:

```bash
vendor/bin/typo3 tmexport:types -t csv -m types.csv
```

Example for JSON:

```bash
vendor/bin/typo3 tmexport:types -t json
```

**6. Fix duplicate MM relations command ( only if needed ) :**

  - 6.0. Exécuter la commande de correction des doublons MM :
    ```bash
    vendor/bin/typo3 tmupgrade:fixdatabaseerrors
    ```
  - 6.1. Exécuter la commande de update schema :
    ```bash
    vendor/bin/typo3 database:updateschema "*.add,*.change"
    ```

**7. Run Upgrade Wizards:**

```bash
vendor/bin/typo3 tmupgrade:run
```

**8. Copy Rector & Fractor configuration files to your project:**

```bash
cp vendor/toumoro/tm-migration/Resources/Private/Config/Rector/rector_v13.php .
cp vendor/toumoro/tm-migration/Resources/Private/Config/Fractor/fractor_v13.php .
```

**9. Run Rector & Fractor in dry-run mode (simulation):**

```bash
vendor/bin/rector process --debug --dry-run 2>&1 | tee rector-dryrun.txt
vendor/bin/fractor process --dry-run 2>&1 | tee fractor-dryrun.txt
```

**10. Apply Rector & Fractor corrections:**

```bash
vendor/bin/rector process
vendor/bin/fractor process
```

> **Tip:**
> Using `--debug` with Rector avoids issues caused by parallel processing.

**11. Import SQL files (before and after database schema update):**

```bash
vendor/bin/typo3 tmupgrade:importsql -f [FILE_NAME]
vendor/bin/typo3 tmupgrade:importsql -d [DIRECTORY]
```

Example:

```bash
vendor/bin/typo3 tmupgrade:importsql -f migration.sql
vendor/bin/typo3 tmupgrade:importsql -d before-updateschema
```

**12. Seperate history entries from sys_log command ( only if migrating a site from TYPO3 version < 9.5 ):**

```bash
vendor/bin/typo3 tmupgrade:sepearate-syshistory-from-syslog -d [DAYS] -l [LIMIT]
```

---

## Run All Steps Script:

- Use this script to re-simulate migration steps or for the day of production deployment.

```bash
chmod +x migration/run.sh
migration/run.sh
```