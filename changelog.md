# MumieTask - Changelog

All important changes to this plugin will be documented in this file.

## [v1.3.4] - 2020-08-04
### Fixed
- Fixed an issue where you couldn't link an entire course in a language for which there is no regular task defined.

### Added
- Added infobox to MUMIE Task form which explains that embedded MUMIE Tasks don't work in Safari due to technical limitations.
- If an embedded MUMIE Task is opened with Safari, it will open in an tab instead of an iFrame.

## [v1.3.3] - 2020-06-17
### Fixed
- Fixed minified js files

### Added
- Admin can now decide whether grades should be shared between courses

## [v1.3.2] - 2020-06-09
### Added
- When creating a new MUMIE Task, the user's prefered language is set as default.
- MUMIE course names are now available in multiple languages
- MUMIE Tasks can now link an entire course at once. Grades will not be synchronized for these kind of activities.
- Teachers can now create MUMIE Tasks for LEMON servers

### Fixed
- Course selection doesn't change anymore if selected language is updated

## [v1.3.1] - 2020-03-03
### Added
- MUMIE Problems can now be added to the server-course-task structure. This means that the use of tasks that are not part of the offical server structre is now supported as well.

### Changed
- Names for MUMIE Tasks that were entered by the user themself are no longer overwritten, when selecting another problem.
- When creating a MUMIE Task with drag&drop, the plugin now tries get a human readable name from the server structure and then sets it as a new default name.

### Fixed.
- Description can now be set for MUMIE Tasks
- Grades are now longer synchronized, if the decision about gradepools is still pending.

## [v1.3.0] - 2020-02-04
### Fixed
- Capabilities are now given a proper name.
- The capability of auth_mumie to add MUMIE servers can now be removed without causing an error in the add MUMIE Task form.
- User IDs are now hashed for SSO if a MUMIE Task was restored from a backup.

### Added
- When creating a MUMIE Task, MUMIE problems can be filtered by keywords.
- A due date can now be set for MUMIE Tasks.
- Automatically add MUMIE Tasks by dragging and dropping MUMIE Problems from MUMIE courses into a MOODLE course.
- Sharing grades for the same MUMIE problems with other MOODLE courses can be disabled.
- Prepared MUMIE Task creation via drag&drop from MUMIE. This will be possible after the next release of MUMIE.

## [v1.2] - 2019-11-05
Attention: Installing this update will create new MUMIE acounts for all users. Old MUMIE tasks will keep working, but they wont share a grade pool with newly created ones.
### Added
- MOODLE userid is now hashed to improve data security.

### Fixed
- Error messages in mod_form are now displayed properly.


## [v1.1] - 2019-09-26
### Fixed
- The MumieTask form now works properly in the 'Clean' moodle theme during activity creation.


