# Changelog

All notable changes to `filament-media-manager` will be documented in this file.

## v0.9.9 - 2026-03-19

### Fixed
- Resolved synchronization issues between the Media Browser and Rich Editor/Media Picker by implementing more robust JavaScript state management.
- Fixed an issue where original filenames were not preserved during upload in the Media Browser, causing them to use temporary hashes instead.

## v0.9.4 - 2026-03-18

### Added
- Support for restricting file types in `MediaManagerRichContentPlugin` using `acceptedFileTypes()`.

### Fixed
- Resolved an issue where non-accepted file types could still be selected in the `MediaBrowser` when using the rich editor integration.

## v0.7.0 - 2026-03-14

### Fixed
- Resolved migration publishing errors by merging multiple migration stubs into a single unified migration file.
- Fixed missing migration stub paths in `MediaManagerServiceProvider`.

### Changed
- **Breaking**: Refactored `InteractsWithMediaFiles` trait to be more generic. Model-specific relationships like `avatar` and `cv` have been removed from the trait and should now be defined directly in the model (e.g., `User` model).
- Reorganized documentation to prioritize "Plugin Registration" and "Prepare Model" sections.
- Improved `MediaPicker` examples in documentation to show relationship-based usage.

### Added
- Comprehensive testing suite covering components, relationships, and publishing workflows.
- Instruction for multi/polymorphic relationships in documentation.

### Added
- Expanded plugin customization support for navigation (group, label, icon, sort, registration condition).
- Support for custom header and footer widgets on the Media Manager page.
- Support for custom header and footer views on the Media Manager page.

### Changed
- Refactored plugin customization pattern to follow Filament best practices and avoid early page instantiation issues.
- Updated documentation to prioritize Tailwind CSS v4 and removed legacy v3 instructions.

## v0.2.5 - 2026-03-13

### Changed
- Refined feature ordering and credits in README.

## v0.2.4 - 2026-03-13

### Changed
- Reordered README sections to prioritize feature highlights.

## v0.2.3 - 2026-03-13

### Changed
- Finalized documentation and credits.

## v0.2.2 - 2026-03-13

### Changed
- Updated documentation with appropriate credits for `SelectTree` and `MediaAction`.

## v0.2.1 - 2026-03-13

### Changed
- Updated plugin features in README.

## v0.2.0 - 2026-03-13

### Added
- Integrated `codewithdennis/filament-select-tree` for hierarchical folder selection in the "Move" action.
- Added file count badges in the folder tree selection.
- Enabled branch node selection in the folder tree.

### Fixed
- Fixed asset loading for `filament-select-tree` to ensure styles and scripts are available in the Media Manager browser.
- Resolved "Call to a member function parent() on null" error in `SelectTree` when used within the component action context.

## v0.1.0 - 2026-03-13

- Initial release of the standalone Filament v5 Media Manager plugin.
