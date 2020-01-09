# MumieTask - Changelog

All important changes to this plugin will be documented in this file.

## [v1.3] TODO: DATE
### Fixed
- Capabilities are now given a proper name
- The capability of auth_mumie to add MUMIE servers can now be removed without causing an error in the add MUMIE Task form.
- User IDs are now hashed for SSO if a MUMIE Task was restored from a backup

### Added
- When creating a MUMIE Task, MUMIE problems can be filtered by keywords.
- A due date can now be set for MUMIE Tasks
- Automatically add MUMIE Tasks by dragging and dropping MUMIE Problems from MUMIE courses into a MOODLE course.
- Sharing grades for the same MUMIE problems with other MOODLE courses can be disabled

## [v1.2] - 2019-11-05
Attention: Installing this update will create new MUMIE acounts for all users. Old MUMIE tasks will keep working, but they wont share a grade pool with newly created ones.
### Added
- MOODLE userid is now hashed to improve data security

### Fixed
- Error messages in mod_form are now displayed properly


## [v1.1] - 2019-09-26
### Fixed
- The MumieTask form now works properly in the 'Clean' moodle theme during activity creation.


