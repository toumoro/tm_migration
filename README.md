
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

#### Export CTypes and List Types

Export `CType` and `list_type` values to JSON or CSV:

```bash
vendor/bin/typo3 export:types -t [FILE_TYPE] -m [FILE_NAME]
```

Example:

```bash
vendor/bin/typo3 export:types -t csv -m types.csv
```

---

#### Fix duplicate MM relations

Clean up duplicate entries in MM relation tables (e.g. `sys_category_record_mm`):

```bash
vendor/bin/typo3 upgrade:fixdatabaseerrors
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

- **SQL Migration Upgrade Wizard**  
  Upgrade wizard that allows execution of custom SQL scripts during the migration process, useful for applying additional database adjustments.  
  ⚠️ **Notice:** To use this wizard, you must create a `migration.sql` file at the root of your TYPO3 project. This file should contain the SQL statements you want to apply during the migration.

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