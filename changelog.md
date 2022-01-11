# MumieTask - Changelog

All important changes to this plugin will be documented in this file.

## [v1.4.6] - [TODO]

### Fixed
- Fixed an issue where an invalid MUMIE server crashes MUMIE Task form. Invalid servers are temporarily removed and a warning is displayed.

## [v1.4.5] - 2021-09-01
### Fixed
- Fixed an issue, where adding MUMIE Tasks via drag&drop did not create entries in the Moodle Gradebook.  

## [v1.4.4] - 2021-07-29
### Added
- Added possibility to select *ungraded* articles, chapters and courses in problem selector.

### Changed
- Removed "use entire course" button. You can create a MUMIE Task linking to the course overview page by selecting the respective problem in problem selector. It's listed under **Type -> Overview**.

### Fixed
- Added styling for warning and info badges (for deadline, passing grade etc) in course view for Moodle 3.11

## [v1.4.3] - 2021-06-25
### Fixed
- Fixed an issue on the MUMIE grading page where sorting the student list by either *Due date extension* or *Submissions* caused an error

## [v1.4.2] - 2021-03-19
### Added
- Implemented Privacy-API

### Changed
- Streamlined ambiguous use of deadline and due date.
- Added link to wiki in mod_form

### Fixed
- Properties can now be applied to other MUMIE Tasks, if MUMIE Server is missing

## [v1.4.1] - 2021-02-26

### Video Summary
- <a href="https://www.youtube.com/watch?v=h9fr99qLHuc">New Features in Version 1.4</a>

### Changed
- Added passing grade as tag to MUMIE Task in course view

### Added
- Teachers that open a MUMIE Task now are shown a new overview page
- Teachers can now grant individual due date extensions to students
- Teachers can now see timestamps and results of all submissions a student has made for a MUMIE Task
- Teachers can now select a submission to overwrite the current grade in the gradebook
- Due dates and due date extensions are now shown in the calendar
- The MUMIE Task properties _duedate_, _max points_ and _launch container_ can now be changed for multiple MUMIE Tasks at once

### Fixed
- Removed an error message that was displayed in course view, if the "use complete course" option was selected for a MUMIE Task.

## [v1.4.0] - 2020-12-10
### Fixed
- Missing server configuration no longer disables js in mod_mumie form
- Corrected some spelling errors in strings and code documentation

### Changed
- MUMIE Problems are no longer selected with internal filter and dropdown menu. Instead, an external and more advanced problem browser (powered by LEMON) is opened.

## [v1.3.6] - 2020-10-29
### Fixed
- Admin settings regarding gradePool are no longer ignored when Tasks are imported via d&d
- Properties dueDate and isGraded are now properly saved during duplication and backup

### Added
- It's now possible to import multiple MUMIE Tasks via drag & drop at once

### Changed
- "New Window"-mode is now the default option for new MUMIE Tasks
- Added warning about potential issues with embedded MUMIE Tasks
- Changing a MUMIE Task's problem now resets its grades

## [v1.3.5] - 2020-09-16
### Fixed
- Fixed an issue where max grade was always displayed as 100 in gradebook. 
Existing MUMIE Tasks will __NOT__ be updated automatically. If you want to the correct max grade to be displayed, just open the activity and click save.

## [v1.3.4] - 2020-08-04
### Fixed
- Fixed an issue where you couldn't link an entire course in a language for which there is no regular task defined.

### Added
- Added info box to MUMIE Task form which explains that embedded MUMIE Tasks don't work in Safari due to technical limitations.
- If an embedded MUMIE Task is opened with Safari, it will open in a tab instead of an iFrame.

## [v1.3.3] - 2020-06-17
### Fixed
- Fixed minified js files

### Added
- Admin can now decide whether grades should be shared between courses

## [v1.3.2] - 2020-06-09
### Added
- When creating a new MUMIE Task, the user's preferred language is set as default.
- MUMIE course names are now available in multiple languages
- MUMIE Tasks can now link an entire course at once. Grades will not be synchronized for these kinds of activities.
- Teachers can now create MUMIE Tasks for LEMON servers

### Fixed
- Course selection doesn't change anymore if the selected language is updated

## [v1.3.1] - 2020-03-03
### Added
- MUMIE Problems can now be added to the server-course-task structure. This means that the use of tasks that are not part of the official server structure is now supported as well.

### Changed
- Names for MUMIE Tasks that were entered by the user themselves are no longer overwritten, when selecting another problem.
- When creating a MUMIE Task with drag&amp;drop, the plugin now tries to get a human-readable name from the server structure and then sets it as a new default name.

### Fixed.
- Description can now be set for MUMIE Tasks
- Grades are no longer synchronized if the decision about gradePools is still pending.

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
- Prepared MUMIE Task creation via drag&amp;drop from MUMIE. This will be possible after the next release of MUMIE.

## [v1.2] - 2019-11-05
Attention: Installing this update will create new MUMIE accounts for all users. Old MUMIE tasks will keep working, but they won't share a grade pool with newly created ones.
### Added
- MOODLE userId is now hashed to improve data security.

### Fixed
- Error messages in mod_form are now displayed properly.


## [v1.1] - 2019-09-26
### Fixed
- The MumieTask form now works properly in the 'Clean' moodle theme during activity creation.


