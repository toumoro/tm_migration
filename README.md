
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
vendor/bin/typo3 export:types -t [FILE_TYPE] -m [FILE_NAME]
```

Example:

```bash
vendor/bin/typo3 export:types -t csv -m types.csv
```

---

#### Fix duplicate MM relations Command

Clean up duplicate entries in MM relation tables (e.g. `sys_category_record_mm`):

```bash
vendor/bin/typo3 upgrade:fixdatabaseerrors
```

---

#### SQL Migration Command

Command that allows execution of custom SQL scripts during the migration process, useful for applying additional database adjustments.  

```bash
vendor/bin/typo3 upgrade:importsql -f [FILE_NAME]
```

---

#### Clear sys_log Command

Command that clears the sys_log entries not related to sys_history and older than -d Days with limit -l Limit.  

```bash
vendor/bin/typo3 upgrade:clearsyslog -d [DAYS] -l [LIMIT]
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
