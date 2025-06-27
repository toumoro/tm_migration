
# tm_migration

**TYPO3 extension that brings together essential tools for major TYPO3 migrations.**

---

## 🚀 Features

### ✅ Dependencies

This extension relies on:

- `typo3-fractor`
- `typo3-rector`
- `core-upgrader`

---

### 🛠 CLI Commands

#### Export CTypes and List Types Command

Export `CType` and `list_type` values to JSON or CSV:

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

---

#### Fix duplicate MM relations Command

Clean up duplicate entries in MM relation tables (e.g. `sys_category_record_mm`):

```bash
vendor/bin/typo3 tmupgrade:fixdatabaseerrors
```

---

#### SQL Migration Command

This command allows the execution of custom SQL scripts as part of the migration process. It's especially useful for applying additional database changes that are not handled automatically by TYPO3 or Doctrine migrations.

##### Prerequisites

Ensure the migration folder is copied to the base directory of your project. This folder contains the necessary SQL scripts and structure required for the migration process.

```bash
chmod +x vendor/toumoro/tm-migration/services/copy-migration-folder.sh
exec bash vendor/toumoro/tm-migration/services/copy-migration-folder.sh
```

##### Usage

```bash
vendor/bin/typo3 tmupgrade:importsql -f [FILE_NAME]
```

- `-f [FILE_NAME]`: Specifies the SQL file to execute. If omitted, it defaults to `migration.sql`.
- `-d [DIRECTORY]`: *(Optional)* Use this to specify a custom directory where the SQL file is located.

  > There are two predefined directories under the `migration` folder:
  > - `before-updateschema`: for SQL scripts that should run **before** the TYPO3 schema update.
  > - `after-updateschema`: for scripts that should run **after** the schema update.

For full list of options, run:

```bash
vendor/bin/typo3 tmupgrade:importsql -h
```

##### Execute the Project-Level Migration and Setup Script

```bash
chmod +x migration/run.sh
exec bash migration/run.sh
```

---

#### Clear sys_log Command

Command that clears the sys_log entries not related to sys_history and older than -d Days with limit -l Limit.  

```bash
vendor/bin/typo3 tmupgrade:clearsyslog -d [DAYS] -l [LIMIT]
```

---

#### Upgrade Wizards Execution Command

Two options are available in the extension settings :
- Specify the **version** from which the upgrade wizards should start executing.
- Exclude specific **upgrade wizard identifiers** from being executed.

```bash
vendor/bin/typo3 tmupgrade:run
```

---

### 🧩 Upgrade Wizards

- **Migrate `list_type` to `CType`**  
  Upgrade wizard to migrate `list_type` plugins to `CType` content elements using a configurable mapping array.  
  The mapping can be customized via the `tm_migration` extension settings.

- **Clean log table**  
  Upgrade wizard to delete or truncate entries from `sys_log` based on the retention period set in the extension settings.

- **Migrate grid elements to container**  
  Upgrade wizard to migrate `grid elements` to `container` content elements.

---

### ⚙️ Rector & Fractor Configurations

Sample Rector and Fractor configurations are provided in:

```
packages/tm_migration/Resources/Private/Config
```

To copy them into your project:

```bash
cp packages/tm_migration/Resources/Private/Config/* YOUR_TARGET_DIRECTORY/
```

---

### 📝 Dry Runs (Simulations)

Before applying automatic fixes, you can run Rector and Fractor in dry-run mode to preview changes:

Run Rector dry-run:

```bash
vendor/bin/rector process --debug --dry-run 2>&1 | tee rector-dryrun.txt
```

Run Fractor dry-run:

```bash
vendor/bin/fractor process --dry-run 2>&1 | tee fractor-dryrun.txt
```

---

### ⚡ Apply automatic fixes

When ready, apply the code modifications:

```bash
vendor/bin/rector process --debug
vendor/bin/fractor process
```

> ℹ️ **Tip:**  
> Using `--debug` with Rector avoids issues caused by parallel processing.


### Update composer dependencies to the latest version (to be tested and reviewed)
```bash
composer require $(composer show -s --format=json | jq '.requires | keys | map(.+" ") | add' -r)
composer require --dev $(composer show -s --format=json | jq '.devRequires | keys | map(.+" ") | add' -r)
```
