# Changelog
All notable changes to this extension will be documented in this file.

## [13.4.9] - 2025-09-25
### Added
- Rename `ClearSysLogCommand` to `SeperateSyshistoryFromSyslogCommand`.

## [13.4.8] - 2025-09-19
### Added
- Add a `README.md` file to the `migrations` folder to detail the list of steps to follow when deploying a migrated site.
- Exclude certain classes from the Rector rule `GeneralUtilityMakeInstanceToConstructorPropertyRector`.

## [13.4.7] - 2025-09-15
### Fixed
- Exclude the `source_path` field that begins with 'https://' and 'http://' in the `FixRedirectsUpgradeWizard` file.

## [13.4.6] - 2025-09-01
### Fixed
- Fixed regex redirection logic in `FixRedirectsUpgradeWizard`.

## [13.4.5] - 2025-07-30
### Added
- Update `run.sh` file to include the installation of the `tm_migration` package.

## [13.4.4] - 2025-07-23
### Added
- Upgrade wizard `FixRedirectsUpgradeWizard` to repair invalid redirects caused by migrations from TYPO3 versions 9.5 or earlier.

## [13.4.3] - 2025-07-19
### Added
- XCLASS override for `MigratePagesLanguageOverlayUpdate` to fix file references in translated (overlay) page records.
- XCLASS override for `WorkspacesNotificationSettingsUpdate`

## [13.4.2] - 2025-06-27
### Fixed
- composer.json dependencies updated for compatibility with TYPO3 v12.
### Added
- Added a -d option to the SQL script runner to allow execution of scripts from a specified directory.

## [13.4.1] - 2025-06-20
### Added
- Added a CLI command (tmupgrade:run) that allows executing upgrade wizards from a specific version, with an option to exclude selected wizards.