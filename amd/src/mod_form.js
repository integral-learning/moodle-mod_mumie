define(['jquery', 'core/templates', 'core/modal_factory', 'auth_mumie/mumie_server_config', 'core/ajax'],
    function() {
        const addServerButton = document.getElementById("id_add_server_button");
        const missingConfig = document.getElementsByName("mumie_missing_config")[0];
        let lmsSelectorUrl;
        let systemLanguage;
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
             * Send a success message to problem selector window
             * @param {string} message
             */
            function sendSuccess(message = '') {
                sendResponse({
                    success: true,
                    message: message
                });
            }

            /**
             * Send a failure message to problem selector window
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
                    const isGraded = importObj.isGraded !== false;
                    const worksheet = importObj.worksheet ?? null;
                    try {
                        courseController.setCourse(importObj.path_to_coursefile);
                        langController.setLanguage(importObj.language);
                        taskController.setSelection(importObj.link, importObj.language, importObj.name);
                        taskController.setIsGraded(isGraded);
                        worksheetController.setWorksheet(worksheet);
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
                // TODO write function to determine whether SSO should be used
                const useSSO = true;
                if (useSSO) {
                    return 'http://moodledev.mumie.net:8050/auth/mumie/problem_selector.php?'
                        + 'org='
                        + mumieOrg
                        + '&serverUrl='
                        + encodeURIComponent(serverController.getSelectedServer().urlprefix)
                        + '&problemLang='
                        + langController.getSelectedLanguage()
                        + '&origin=' + encodeURIComponent(window.location.origin)
                        + '&gradingType=' + gradingType
                        + (selection ? '&selection=' + selection : '');
                }
                return lmsSelectorUrl
                    + '/lms-problem-selector?'
                    + 'org='
                    + mumieOrg
                    + '&serverUrl='
                    + encodeURIComponent(serverController.getSelectedServer().urlprefix)
                    + '&problemLang='
                    + langController.getSelectedLanguage()
                    + '&origin=' + encodeURIComponent(window.location.origin)
                    + '&uiLang=' + systemLanguage
                    + '&gradingType=' + gradingType
                    + '&multiCourse=true'
                    + '&worksheet=true'
                    + (selection ? '&selection=' + selection : '');
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
                        problemSelectorWindow = window.open(
                          lmsSelectorUrl
                          + '/lms-problem-selector?'
                          + "serverUrl="
                          + encodeURIComponent(serverController.getSelectedServer().urlprefix)
                          + '&gradingType=all',
                          "_blank",
                          'toolbar=0,location=0,menubar=0'
                        );
                    };
                },
                disable: function() {
                    problemSelectorButton.disabled = true;
                }
            };
        })();

        const courseController = (function() {
            const courseNameElem = document.getElementById("id_mumie_course");
            const coursefileElem = document.getElementsByName("mumie_coursefile")[0];


            /**
             * Update the hidden input field with the selected course's course file path
             *
             * @param {string} coursefile
             */
            function updateCoursefilePath(coursefile) {
                coursefileElem.value = coursefile;
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
                    return courses.find(course => course.coursefile === coursefileElem.value);
                },
                setCourse: function(courseFile) {
                    updateCoursefilePath(courseFile);
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
            const isGradedElem = document.getElementById('id_mumie_isgraded');
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
            function updateTaskDisplayElemement(localizedLink) {
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
                updateTaskDisplayElemement(localizedLink);
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
             * Form inputs related to grades should be disabled, if the MUMIE Task is not graded.
             */
            function updateGradeEditability() {
                const disabled = isGradedElem.value === '0';
                document.getElementById('id_points').disabled = disabled;
                document.getElementById('id_gradepass').disabled = disabled;
                document.getElementById('id_duedate_enabled').disabled = disabled;
                document.getElementById('id_gradecat').disabled = disabled;
            }

            return {
                init: function() {
                    updateTaskDisplayElemement(taskSelectionInput.value);
                },
                setSelection: function(link, language, name) {
                    updateTaskUri(link, language);
                    updateName(name);
                },
                setIsGraded: function(isGraded) {
                    if (isGraded === null) {
                        isGradedElem.value = null;
                    }
                    isGradedElem.value = isGraded ? '1' : '0';
                    updateGradeEditability();
                },
                getGradingType: function() {
                    const isGraded = isGradedElem.value;
                    if (isGraded === '1') {
                        return 'graded';
                    } else if (isGraded === '0') {
                        return 'ungraded';
                    }
                    return 'all';
                },
                getDelocalizedTaskLink: function() {
                    return delocalizeLink(taskSelectionInput.value);
                }
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
                    'key':  errorKey,
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
            init: function(contextid, prbSelectorUrl, lang) {
                lmsSelectorUrl = prbSelectorUrl;
                systemLanguage = lang;
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
                }
                multiTaskEditController.init();
                if (addServerButton) {
                    require(['auth_mumie/mumie_server_config'], function(MumieServer) {
                        MumieServer.init(addServerButton, contextid);
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
