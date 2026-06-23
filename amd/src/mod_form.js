define(['jquery', 'core/templates', 'core/modal_factory', 'auth_mumie/mumie_server_config', 'core/ajax'],
    function() {
        const addServerButton = document.getElementById("id_add_server_button");
        const missingConfig = document.getElementsByName("mumie_missing_config")[0];
        let lmsSelectorUrl;
        let systemLanguage;
        let contextId;


        const durationController = (function() {

            const durationSelector = document.getElementById('id_duration_selector');
            const gradedElem = document.getElementById('id_mumie_isgraded');

            /**
             * Returns if task is ungraded like courses and articles.
             */
            function isUngraded() {
                return gradedElem.value === '0';
            }

            /**
             * Updates the visibility of elements around duration selector.
             */
            function updateDurationElements() {
                const disabled = isUngraded();
                if (disabled) {
                    durationSelector.setAttribute('disabled', 'disabled');
                    durationSelector.value = 'unlimited';
                } else {
                    durationSelector.removeAttribute('disabled');
                }

                const displayNone = 'none';

                if (durationSelector.value === 'unlimited') {
                    document.getElementById('fitem_id_unlimited_info').style.display = '';

                    document.getElementById('fitem_id_timelimit').style.display = displayNone;
                    document.getElementById('fitem_id_timelimit_info').style.display = displayNone;
                    document.getElementById('fitem_id_duedate').style.display = displayNone;
                    document.getElementById('fitem_id_duedate_info').style.display = displayNone;
                } else if (durationSelector.value === 'duedate') {
                    document.getElementById('fitem_id_duedate').style.display = '';
                    document.getElementById('fitem_id_duedate_info').style.display = '';

                    document.getElementById('fitem_id_unlimited_info').style.display = displayNone;
                    document.getElementById('fitem_id_timelimit').style.display = displayNone;
                    document.getElementById('fitem_id_timelimit_info').style.display = displayNone;
                } else if (durationSelector.value === 'timelimit') {
                    document.getElementById('fitem_id_timelimit').style.display = '';
                    document.getElementById('fitem_id_timelimit_info').style.display = '';

                    document.getElementById('fitem_id_duedate').style.display = displayNone;
                    document.getElementById('fitem_id_duedate_info').style.display = displayNone;
                    document.getElementById('fitem_id_unlimited_info').style.display = displayNone;
                }

                const duedatePropRow = document
                    .querySelector('[name="mumie_multi_edit_property"][value="duedate"]')
                    ?.closest('tr');
                if (duedatePropRow) {
                    const show = durationSelector.value === 'duedate';
                    duedatePropRow.style.display = show ? '' : displayNone;
                    if (!show) {
                        const duedatePropertyCheckbox = duedatePropRow.querySelector('[name="mumie_multi_edit_property"]');
                        if (duedatePropertyCheckbox && duedatePropertyCheckbox.checked) {
                            duedatePropertyCheckbox.checked = false;
                            duedatePropertyCheckbox.dispatchEvent(new Event('change'));
                        }
                    }
                }
            }

            return {
                init: function() {
                    durationSelector.onchange = function() {
                        updateDurationElements();
                    };
                    window.addEventListener("load", () => {
                        updateDurationElements();
                    });
                    updateDurationElements();
                },
                setDurationElements: updateDurationElements,
                isUngraded: isUngraded,
                setGradedElemValue: function(isGraded) {
                    gradedElem.value = isGraded ? '1' : '0';
                },
                getGradedElemValue: function() {
                    return gradedElem.value;
                }
            };
        })();

        const serverController = (function() {
            let serverStructure;
            const serverDropDown = document.getElementById("id_server");

            return {
                init: function(structure) {
                    serverStructure = structure;
                },
                getSelectedServer: function() {
                    const selectedServerName = serverDropDown.options[serverDropDown.selectedIndex].text;
                    return serverStructure.find(server => server.name === selectedServerName);
                },
                disable: function() {
                    serverDropDown.disabled = true;
                    removeChildElems(serverDropDown);
                }
            };
        })();

        const problemSelectorController = (function() {
            const problemSelectorButton = document.getElementById('id_prb_selector_btn');
            const multiProblemSelectorButton = document.getElementById('id_multi_problem_selector_btn');
            let problemSelectorWindow;
            const mumieOrg = document.getElementsByName('mumie_org')[0].value;

            /**
             * Send a message to the problem selector window.
             *
             * Don't do anything if there is no problem selector window.
             * @param {Object} response
             */
            function sendResponse(response) {
                if (!problemSelectorWindow) {
                    return;
                }
                problemSelectorWindow.postMessage(JSON.stringify(response), lmsSelectorUrl);
            }

            /**
             * Send a success message to a problem selector window
             * @param {string} message
             */
            function sendSuccess(message = '') {
                sendResponse({
                    success: true,
                    message: message
                });
            }

            /**
             * Send a failure message to a problem selector window
             * @param {string} message
             */
            function sendFailure(message = '') {
                sendResponse({
                    success: false,
                    message: message
                });
            }

            /**
             * Add an event listener that accepts messages from LMS-Browser and updates the selected problem.
             */
            function addMessageListener() {
                window.addEventListener('message', (event) => {
                    if (event.origin !== lmsSelectorUrl) {
                        return;
                    }
                    const importObj = JSON.parse(event.data);

                    if (Array.isArray(importObj)) {
                        taskController.setMultiSelection(importObj);
                        sendSuccess();
                        window.focus();
                        return;
                    }

                    try {
                        applyPickerPayloadToForm(importObj);
                        sendSuccess();
                        window.focus();
                        displayProblemSelectedMessage();
                    } catch (error) {
                        sendFailure(error.message);
                    }
                }, false);
            }

            /**
             * Display a success message in Moodle that a problem was successfully selected.
             */
            function displayProblemSelectedMessage() {
                require(['core/str', "core/notification"], function(str, notification) {
                    str.get_strings([{
                        'key': 'mumie_form_updated_selection',
                        component: 'mod_mumie'
                    }]).done(function(s) {
                        notification.addNotification({
                            message: s[0],
                            type: "info"
                        });
                    }).fail(notification.exception);
                });
            }

            /**
             * Builds the URL to the Problem Selector
             * @returns {string} URL to the Problem Selector
             */
            function buildURL() {
                const gradingType = taskController.getGradingType();
                const selection = taskController.getDelocalizedTaskLink();
                const selectedServer = serverController.getSelectedServer().urlprefix;
                const useSSO = shouldUseSSO(lmsSelectorUrl, selectedServer);
                if (useSSO) {
                    return '/auth/mumie/problem_selector.php?' +
                        'org=' +
                        mumieOrg +
                        '&serverurl=' +
                        encodeURIComponent(selectedServer) +
                        '&problemlang=' +
                        langController.getSelectedLanguage() +
                        '&origin=' + encodeURIComponent(window.location.origin) +
                        '&gradingtype=' + gradingType +
                        '&contextid=' + contextId +
                        (selection ? '&selection=' + selection : '');
                }
                return lmsSelectorUrl +
                    '/lms-problem-selector?' +
                    'org=' +
                    mumieOrg +
                    '&serverUrl=' +
                    encodeURIComponent(selectedServer) +
                    '&problemLang=' +
                    langController.getSelectedLanguage() +
                    '&origin=' + encodeURIComponent(window.location.origin) +
                    '&uiLang=' + systemLanguage +
                    '&gradingType=' + gradingType +
                    '&multiCourse=true' +
                    '&worksheet=true' +
                    (selection ? '&selection=' + selection : '');
            }

            /**
             * Determines whether the Single Sign-On (SSO) should be used when opening the Problem Selector.
             * SSO is only supposed to be used when the Problem Selector URL has the same origin as the
             * URL of the selected MUMIE server.
             *
             * @param {string} problemSelectorUrl - The URL of the problem selector.
             * @param {string} selectedServerUrl - The URL of the selected MUMIE server
             * @returns {boolean} Whether SSO should be used for the Problem Selector or not
             */
            function shouldUseSSO(problemSelectorUrl, selectedServerUrl) {
                return new URL(problemSelectorUrl).origin === new URL(selectedServerUrl).origin;
            }

            return {
                init: function() {
                    problemSelectorButton.onclick = function() {
                        problemSelectorWindow = window.open(buildURL(), '_blank');
                    };

                    window.onclose = function() {
                        sendSuccess();
                    };

                    window.addEventListener("beforeunload", function() {
                        sendSuccess();
                    }, false);

                    addMessageListener();

                    multiProblemSelectorButton.onclick = function(e) {
                        e.preventDefault();
                        const selectedServer = serverController.getSelectedServer().urlprefix;
                        const multiSelectorUrl = shouldUseSSO(lmsSelectorUrl, selectedServer)
                            ? '/auth/mumie/problem_selector.php?' +
                                'org=' + mumieOrg +
                                '&serverurl=' + encodeURIComponent(selectedServer) +
                                '&problemlang=' + langController.getSelectedLanguage() +
                                '&origin=' + encodeURIComponent(window.location.origin) +
                                '&gradingtype=all' +
                                '&contextid=' + contextId +
                                '&multiselect=true'
                            : lmsSelectorUrl +
                                '/lms-problem-selector?' +
                                'org=' + mumieOrg +
                                '&serverUrl=' + encodeURIComponent(selectedServer) +
                                '&problemLang=' + langController.getSelectedLanguage() +
                                '&origin=' + encodeURIComponent(window.location.origin) +
                                '&uiLang=' + systemLanguage +
                                '&gradingType=all' +
                                '&multiCourse=true' +
                                '&worksheet=true' +
                                '&multiSelect=true';
                        problemSelectorWindow = window.open(multiSelectorUrl, "_blank", 'toolbar=0,location=0,menubar=0');
                    };
                },
                disable: function() {
                    problemSelectorButton.disabled = true;
                },
            };
        })();

        /**
         * Apply a picker payload to the form's per-task fields.
         *
         * Single source of truth for picker-payload → form-field mapping.
         * Used by both the single-task postMessage handler and the multi-task
         * submit loop, so new picker fields only need to be wired here.
         *
         * @param {Object} payload picker payload for one task
         */
        function applyPickerPayloadToForm(payload) {
            const isGraded = payload.isGraded !== false;
            const worksheet = payload.worksheet ?? null;
            courseController.setCourse(payload.path_to_coursefile);
            langController.setLanguage(payload.language);
            taskController.setSelection(payload.link, payload.language, payload.name);
            taskController.setIsGraded(isGraded);
            worksheetController.setWorksheet(worksheet);
        }

        const courseController = (function() {
            const courseNameElem = document.getElementById("id_mumie_course");
            const courseFileElem = document.getElementsByName("mumie_coursefile")[0];


            /**
             * Update the hidden input field with the selected course's course file path
             *
             * @param {string} coursefile
             */
            function updateCourseFilePath(coursefile) {
                courseFileElem.value = coursefile;
                updateCourseName();
            }

            /**
             * Update displayed course name.
             */
            function updateCourseName() {
                const selectedCourse = courseController.getSelectedCourse();
                const selectedLanguage = langController.getSelectedLanguage();
                if (!selectedCourse || !selectedLanguage) {
                    return;
                }
                courseNameElem.value = selectedCourse.name
                    .find(translation => translation.language === selectedLanguage)?.value;
            }

            return {
                init: function() {
                    updateCourseName();
                },
                getSelectedCourse: function() {
                    const courses = serverController.getSelectedServer().courses;
                    return courses.find(course => course.coursefile === courseFileElem.value);
                },
                setCourse: function(courseFile) {
                    updateCourseFilePath(courseFile);
                }
            };
        })();

        const langController = (function() {
            const languageElem = document.getElementById("id_language");
            return {
                getSelectedLanguage: function() {
                    return languageElem.value;
                },
                setLanguage: function(lang) {
                    languageElem.value = lang;
                }
            };
        })();

        const taskController = (function() {
            const taskSelectionInput = document.getElementsByName("taskurl")[0];
            const nameElem = document.getElementById("id_name");
            const taskDisplayElement = document.getElementById("id_task_display_element");
            const LANG_REQUEST_PARAM_PREFIX = "?lang=";

            /**
             * Update the activity's name in the input field
             * @param {string} name
             */
            function updateName(name) {
                nameElem.value = name;
            }

            /**
             * @param {string} localizedLink
             */
            function updateTaskDisplayElement(localizedLink) {
                taskDisplayElement.value = localizedLink;
            }

            /**
             * Update task uri
             * @param {string} link
             * @param {string} language
             */
            function updateTaskUri(link, language) {
                const localizedLink = localizeLink(link, language);
                taskSelectionInput.value = localizedLink;
                updateTaskDisplayElement(localizedLink);
            }

            /**
             * Add lang request param to link
             * @param {string} link
             * @param {string} language
             * @returns {string} Link with lang request param
             */
            function localizeLink(link, language) {
                return link + LANG_REQUEST_PARAM_PREFIX + language;
            }

            /**
             * Remove lang request param from link
             * @param {string} link Link that may have lang request param
             * @returns {string} Link without lang request param
             */
            function delocalizeLink(link) {
                if (link.includes(LANG_REQUEST_PARAM_PREFIX)) {
                    return link.split(LANG_REQUEST_PARAM_PREFIX)[0];
                }
                return link;
            }

            /**
             * Form inputs related to grades should be disabled if the MUMIE Task is not graded.
             */
            function updateGradeEditability() {
                const disabled = durationController.isUngraded();
                document.getElementById('id_points').disabled = disabled;
                document.getElementById('id_gradepass').disabled = disabled;
                document.getElementById('id_gradecat').disabled = disabled;
                durationController.setDurationElements();
            }

            /**
             * Store selected tasks and update UI for multi-task creation.
             * @param {Array} tasks array of task objects from the selector
             */
            function setMultiSelection(tasks) {
                const tasksField = document.getElementsByName('mumie_multi_tasks')[0];
                const summary = document.getElementById('mumie_multi_tasks_summary');
                if (tasksField) {
                    tasksField.value = JSON.stringify(tasks);
                }
                if (summary) {
                    const taskListItems = tasks.map(task => {
                        const taskListItem = document.createElement('li');
                        taskListItem.textContent = task.name;
                        return taskListItem;
                    });
                    summary.innerHTML = '';
                    const taskCountLabel = document.createElement('div');
                    const taskList = document.createElement('ul');
                    require(['core/str'], function(Str) {
                        Str.get_string('mumie_multi_tasks_selected', 'mod_mumie', tasks.length)
                            .then(function(label) {
                                taskCountLabel.textContent = label;
                            });
                    });
                    taskList.style.margin = '0.3em 0 0 1.2em';
                    taskListItems.forEach(taskListItem => taskList.appendChild(taskListItem));
                    summary.appendChild(taskCountLabel);
                    summary.appendChild(taskList);
                    summary.style.display = 'block';
                }
                const nameField = document.getElementById('id_name');
                nameField.disabled = true;
                nameField.removeAttribute('required');
                const nameFieldContainer = document.getElementById('fitem_id_name');
                if (nameFieldContainer) {
                    nameFieldContainer.querySelectorAll('.req, .text-danger, [title="Required field"]')
                        .forEach(requiredIndicator => { requiredIndicator.style.display = 'none'; });
                }
                const requiredLegend = document.querySelector('.fdescription.required');
                if (requiredLegend) {
                    requiredLegend.style.display = 'none';
                }
            }

            /**
             * Serialize a form into a URL-encoded POST string, omitting form mechanics
             * the webservice does not need (sesskey, qf form marker).
             *
             * Disabled form controls are temporarily enabled so FormData includes them:
             * the picker writes values to display-only inputs (e.g. mumie_course) that
             * the server-side validator still expects to receive.
             *
             * @param {HTMLFormElement} form
             * @returns {string}
             */
            function serializeFormFields(form) {
                const disabledFields = Array.from(form.querySelectorAll(':disabled'));
                disabledFields.forEach(field => { field.disabled = false; });
                try {
                    const omit = ['sesskey', '_qf__mod_mumie_mod_form'];
                    return Array.from(new FormData(form))
                        .filter(([key]) => !omit.includes(key))
                        .map(([key, value]) => encodeURIComponent(key) + '=' + encodeURIComponent(value))
                        .join('&');
                } finally {
                    disabledFields.forEach(field => { field.disabled = true; });
                }
            }

            /**
             * Submit multi-tasks via AJAX, then redirect to course.
             *
             * Builds one full form payload per task by applying each picker payload
             * to the live form and serializing it. The server treats each entry as
             * an independent single-task submission, so picker-payload → form-field
             * mapping lives in applyPickerPayloadToForm and is shared with the
             * single-task path. Validation runs server-side via the shared form
             * pipeline; errors surface via Notification.exception.
             */
            function submitMultiTasks() {
                const tasksField = document.getElementsByName('mumie_multi_tasks')[0];
                const courseId = document.getElementsByName('course')[0]?.value;
                const section = parseInt(new URLSearchParams(window.location.search).get('section') || 0);
                const submitButton = document.getElementById('id_submitbutton');
                const form = submitButton && submitButton.closest('form');
                const tasks = JSON.parse(tasksField.value);

                const tasksFormData = tasks.map(task => {
                    applyPickerPayloadToForm(task);
                    return serializeFormFields(form);
                });

                // applyPickerPayloadToForm leaves the form in single-task mode of the last task;
                // restore the multi-task UI so the form is coherent if the AJAX call fails.
                setMultiSelection(tasks);

                require(['core/ajax', 'core/notification'], function(Ajax, Notification) {
                    Ajax.call([{
                        methodname: 'mod_mumie_create_multiple_tasks',
                        args: {
                            contextid: parseInt(contextId),
                            section: section,
                            tasksformdata: tasksFormData,
                        },
                    }])[0].done(function() {
                        window.location.href = M.cfg.wwwroot + '/course/view.php?id=' + courseId;
                    }).fail(Notification.exception);
                });
            }

            return {
                init: function() {
                    updateTaskDisplayElement(taskSelectionInput.value);
                },
                setSelection: function(link, language, name) {
                    const tasksField = document.getElementsByName('mumie_multi_tasks')[0];
                    if (tasksField) {
                        tasksField.value = '';
                    }
                    const summary = document.getElementById('mumie_multi_tasks_summary');
                    if (summary) {
                        summary.style.display = 'none';
                        summary.innerHTML = '';
                    }
                    const nameField = document.getElementById('id_name');
                    if (nameField) {
                        nameField.disabled = false;
                        nameField.setAttribute('required', 'required');
                    }
                    const nameFieldContainer = document.getElementById('fitem_id_name');
                    if (nameFieldContainer) {
                        nameFieldContainer.querySelectorAll('.req, .text-danger, [title="Required field"]')
                            .forEach(requiredIndicator => { requiredIndicator.style.display = ''; });
                    }
                    const requiredLegend = document.querySelector('.fdescription.required');
                    if (requiredLegend) {
                        requiredLegend.style.display = '';
                    }
                    updateTaskUri(link, language);
                    updateName(name);
                },
                setIsGraded: function(isGraded) {
                    durationController.setGradedElemValue(isGraded);
                    updateGradeEditability();
                },
                getGradingType: function() {
                    const isGraded = durationController.getGradedElemValue();
                    if (isGraded === '1') {
                        return 'graded';
                    } else if (isGraded === '0') {
                        return 'ungraded';
                    }
                    return 'all';
                },
                getDelocalizedTaskLink: function() {
                    return delocalizeLink(taskSelectionInput.value);
                },
                setMultiSelection: setMultiSelection,
                hasMultiTasks: function() {
                    const tasksField = document.getElementsByName('mumie_multi_tasks')[0];
                    return tasksField && !!tasksField.value;
                },
                submitMultiTasks: submitMultiTasks,
            };
        })();


        const multiTaskEditController = (function() {
            const propertySelectionInputs = document.getElementsByName("mumie_multi_edit_property");
            const selectedTaskProperties = document.getElementsByName("mumie_selected_task_properties")[0];
            let selectedTaskProp = [];
            const taskSelectionInputs = document.getElementsByName("mumie_multi_edit_task");
            const selectedTasks = document.getElementsByName("mumie_selected_tasks")[0];
            let selectedTaskIds = [];
            const sectionInputs = document.getElementsByName("mumie_multi_edit_section");

            /**
             * Push an element to an array, if it's not already included.
             *
             * @param {string[]} array
             * @param {string} element
             */
            function pushIfNotExists(array, element) {
                if (!array.includes(element)) {
                    array.push(element);
                }
            }

            /**
             * Set selection listeners for other MUMIE Tasks in the course.
             */
            function setTaskSelectionListeners() {
                taskSelectionInputs.forEach(function(checkbox) {
                    checkbox.onchange = function() {
                        if (!checkbox.checked) {
                            selectedTaskIds = selectedTaskIds.filter(elem => elem !== checkbox.value);
                        } else {
                            selectedTaskIds.push(checkbox.value);
                        }
                        selectedTasks.value = JSON.stringify(selectedTaskIds);
                    };
                });
            }

            /**
             * Show or hide per-task "Working period: not Deadline" warnings based on whether the duedate property is selected.
             */
            function updateNoDuedateWarnings() {
                const duedateSelected = Array.from(propertySelectionInputs)
                    .some(checkbox => checkbox.value === 'duedate' && checkbox.checked);
                document.querySelectorAll('.mumie-form-working-period-not-duedate-warning').forEach(function(elem) {
                    elem.style.display = duedateSelected ? '' : 'none';
                });
            }

            /**
             * Set selection listeners for properties to apply to MUMIE Tasks in the course.
             */
            function setPropertySelectionListeners() {
                propertySelectionInputs.forEach(function(checkbox) {
                    checkbox.onchange = function() {
                        if (!checkbox.checked) {
                            selectedTaskProp = selectedTaskProp.filter(elem => elem !== checkbox.value);
                        } else {
                            selectedTaskProp.push(checkbox.value);
                        }
                        selectedTaskProperties.value = JSON.stringify(selectedTaskProp);
                        updateNoDuedateWarnings();
                    };
                });
            }

            /**
             * Set selection listeners for entire section of MUMIE Tasks in the course
             */
            function setSectionSelectionListeners() {
                sectionInputs.forEach(function(sectionCheckbox) {
                    sectionCheckbox.onchange = function() {
                        if (!sectionCheckbox.checked) {
                            taskSelectionInputs.forEach(function(taskCheckbox) {
                                if (taskCheckbox.getAttribute('section') === sectionCheckbox.value) {
                                    taskCheckbox.checked = false;
                                    selectedTaskIds = selectedTaskIds.filter(elem => taskCheckbox.value !== elem);
                                }
                            });
                        } else {
                            taskSelectionInputs.forEach(function(taskCheckbox) {
                                if (taskCheckbox.getAttribute('section') === sectionCheckbox.value) {
                                    taskCheckbox.checked = true;
                                    pushIfNotExists(selectedTaskIds, taskCheckbox.value);
                                }
                            });
                        }
                        selectedTasks.value = JSON.stringify(selectedTaskIds);
                    };
                });
            }

            return {
                init: function() {
                    setTaskSelectionListeners();
                    setPropertySelectionListeners();
                    setSectionSelectionListeners();
                },
            };
        })();

        const worksheetController = (function() {
            const worksheetElement = document.getElementById("id_mumie_worksheet");
            return {
                setWorksheet: function(worksheet) {
                    if (worksheet) {
                        worksheetElement.setAttribute("value", JSON.stringify(worksheet));
                    } else {
                        worksheetElement.removeAttribute("value");
                    }
                }
            };
        })();

        /**
         *  Disable all dropdown menus and show notification
         * @param {string} errorKey
         */
        function disableDropDownMenus(errorKey) {
            require(['core/str', "core/notification"], function(str, notification) {
                str.get_strings([{
                    'key': errorKey,
                    component: 'mod_mumie'
                }]).done(function(s) {
                    notification.addNotification({
                        message: s[0] + "<b>" + missingConfig.getAttribute("value") + "</b>",
                        type: "problem"
                    });
                }).fail(notification.exception);
            });
            serverController.disable();
            problemSelectorController.disable();
        }

        return {
            init: function(contextIdParam, prbSelectorUrl, lang) {
                lmsSelectorUrl = prbSelectorUrl;
                systemLanguage = lang;
                contextId = contextIdParam;
                const isEdit = document.getElementById("id_name").getAttribute('value');
                const serverStructure = JSON.parse(document.getElementsByName('mumie_server_structure')[0].value);
                if (isEdit && !serverConfigExists()) {
                    disableDropDownMenus('mumie_form_missing_server');
                } else if (!serverStructure.length) {
                    disableDropDownMenus('mumie_form_no_server_conf');
                } else {
                    serverController.init(serverStructure);
                    courseController.init();
                    taskController.init();
                    multiTaskEditController.init();
                    problemSelectorController.init();
                    durationController.init();
                }
                if (addServerButton) {
                    require(['auth_mumie/mumie_server_config'], function(MumieServer) {
                        MumieServer.init(addServerButton, contextId);
                    });
                }

                const submitButton = document.getElementById('id_submitbutton');
                const form = submitButton && submitButton.closest('form');
                if (form) {
                    let cancelClicked = false;
                    const cancelButton = document.getElementById('id_cancel');
                    if (cancelButton) {
                        cancelButton.addEventListener('click', function() {
                            cancelClicked = true;
                        });
                    }
                    form.addEventListener('submit', function(e) {
                        if (cancelClicked) {
                            cancelClicked = false;
                            return;
                        }
                        if (taskController.hasMultiTasks()) {
                            e.preventDefault();
                            taskController.submitMultiTasks();
                        }
                    });
                }
            }
        };

        /**
         * Remove all child elements of a given html element
         * @param {Object} elem
         */
        function removeChildElems(elem) {
            while (elem.firstChild) {
                elem.removeChild(elem.firstChild);
            }
        }

        /**
         * Check, if the flag for an existing config is set
         * @returns {boolean}
         */
        function serverConfigExists() {
            return document.getElementsByName("mumie_missing_config")[0].getAttribute("value") === "";
        }
    });
