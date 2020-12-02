define(['jquery', 'core/templates', 'core/modal_factory', 'auth_mumie/mumie_server_config', 'core/ajax'],
    function() {
        var addServerButton = document.getElementById("id_add_server_button");
        var missingConfig = document.getElementsByName("mumie_missing_config")[0];
        var lmsSelectorUrl;
        var systemLanguage;
        var serverController = (function() {
            var serverStructure;
            var serverDropDown = document.getElementById("id_server");

            return {
                init: function(structure) {
                    serverStructure = structure;
                    serverDropDown.onchange = function() {
                        courseController.updateOptions();
                        langController.updateOptions();
                        taskController.updateOptions();
                    };
                },
                getSelectedServer: function() {
                    var selectedServerName = serverDropDown.options[serverDropDown.selectedIndex].text;

                    for (var i in serverStructure) {
                        var server = serverStructure[i];
                        if (server.name == selectedServerName) {
                            return server;
                        }
                    }
                    return null;
                },
                disable: function() {
                    serverDropDown.disabled = true;
                    removeChildElems(serverDropDown);
                },
                getAllServers: function() {
                    return serverStructure;
                }
            };
        })();

        var problemSelectorController = (function() {
            var problemSelectorButton = document.getElementById('id_prb_selector_btn');
            var problemSelectorWindow;
            var mumieOrg = document.getElementsByName('mumie_org')[0].value;

            /**
             * Send a message to the problem selector window.
             *
             * Don't do anything, if there is no problem selector window.
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
                    if (event.origin != lmsSelectorUrl) {
                        return;
                    }
                    var importObj = JSON.parse(event.data);
                    try {
                        langController.setLanguage(importObj.language);
                        taskController.updateOptions(importObj.link + '?lang=' + importObj.language);
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

            return {
                init: function() {
                    problemSelectorButton.onclick = function() {
                        problemSelectorWindow = window.open(
                            lmsSelectorUrl
                                + '/lms-problem-selector?'
                                + 'org='
                                + mumieOrg
                                + '&serverUrl='
                                + encodeURIComponent(serverController.getSelectedServer().urlprefix)
                                + '&lang='
                                + langController.getSelectedLanguage()
                                + '&problem=' + taskController.getSelectedTask().link
                                + '&origin=' + encodeURIComponent(window.location.origin)
                                + '&moodleLang=' + systemLanguage
                            , '_blank'
                        );
                    };

                    window.onclose = function() {
                        sendSuccess();
                    };

                    window.addEventListener("beforeunload", function() {
                        sendSuccess();
                     }, false);

                    addMessageListener();
                },
                disable: function() {
                    problemSelectorButton.disabled = true;
                }
            };
        })();

        var courseController = (function() {
            var courseDropDown = document.getElementById("id_mumie_course");
            var coursefileElem = document.getElementsByName("mumie_coursefile")[0];

            /**
             * Add a new option the the 'MUMIE Course' drop down menu
             * @param {Object} course
             */
            function addOptionForCourse(course) {
                var optionCourse = document.createElement("option");
                var selectedLanguage = langController.getSelectedLanguage();
                var name;
                // If the currently selected server is not available on the server, we need to select another one.
                if (!course.languages.includes(selectedLanguage)) {
                    name = course.name[0];
                } else {
                    for (var i in course.name) {
                        if (course.name[i].language == selectedLanguage) {
                            name = course.name[i];
                        }
                    }
                }
                optionCourse.setAttribute("value", name.value);
                optionCourse.text = name.value;
                courseDropDown.append(optionCourse);
            }

            /**
             * Update the hidden input field with the selected course's course file path
             */
            function updateCoursefilePath() {
                coursefileElem.value = courseController.getSelectedCourse().coursefile;
            }

            return {
                init: function(isEdit) {
                    courseDropDown.onchange = function() {
                        updateCoursefilePath();
                        langController.updateOptions();
                        taskController.updateOptions();
                    };
                    courseController.updateOptions(isEdit ? coursefileElem.value : false);
                },
                getSelectedCourse: function() {
                    var selectedCourseName = courseDropDown.options[courseDropDown.selectedIndex].text;
                    var courses = serverController.getSelectedServer().courses;
                    for (var i in courses) {
                        var course = courses[i];
                        for (var j in course.name) {
                            if (course.name[j].value == selectedCourseName) {
                                return course;
                            }
                        }
                    }
                    return null;
                },
                disable: function() {
                    courseDropDown.disabled = true;
                    removeChildElems(courseDropDown);
                },
                updateOptions: function() {
                    var selectedCourseFile = coursefileElem.value;
                    removeChildElems(courseDropDown);
                    courseDropDown.selectedIndex = 0;
                    var courses = serverController.getSelectedServer().courses;
                    for (var i in courses) {
                        var course = courses[i];
                        addOptionForCourse(course);
                        if (course.coursefile == selectedCourseFile) {
                            courseDropDown.selectedIndex = courseDropDown.childElementCount - 1;
                        }
                    }
                    updateCoursefilePath();
                }
            };
        })();

        var langController = (function() {
            var languageDropDown = document.getElementById("id_language");

            /**
             * Add a new option to the language drop down menu
             * @param {string} lang the language to add
             */
            function addLanguageOption(lang) {
                var optionLang = document.createElement("option");
                optionLang.setAttribute("value", lang);
                optionLang.text = lang;
                languageDropDown.append(optionLang);
            }
            return {
                init: function() {

                    languageDropDown.onchange = function() {
                        taskController.updateOptions();
                        courseController.updateOptions();
                    };
                    langController.updateOptions();
                },
                getSelectedLanguage: function() {
                    return languageDropDown.options[languageDropDown.selectedIndex].text;
                },
                setLanguage: function(lang) {
                    for (var i in languageDropDown.options) {
                        var option = languageDropDown.options[i];
                        if (option.value == lang) {
                            languageDropDown.selectedIndex = i;
                            courseController.updateOptions();
                            return;
                        }
                    }
                    throw new Error("Selected language not available");
                },
                disable: function() {
                    languageDropDown.disabled = true;
                    removeChildElems(languageDropDown);
                },
                updateOptions: function() {
                    var currentLang = langController.getSelectedLanguage();
                    removeChildElems(languageDropDown);
                    languageDropDown.selectedIndex = 0;
                    var languages = courseController.getSelectedCourse().languages;
                    for (var i in languages) {
                        var lang = languages[i];
                        addLanguageOption(lang);
                        if (lang == currentLang) {
                            languageDropDown.selectedIndex = languageDropDown.childElementCount - 1;
                        }
                    }
                }
            };
        })();

        var taskController = (function() {
            var taskDropDown = document.getElementById("id_taskurl");
            var nameElem = document.getElementById("id_name");
            var useCompleteCourseElem = document.getElementById("id_mumie_complete_course");

            /**
             * Update the activity's name in the input field
             */
            function updateName() {
                if (!isCustomName()) {
                    nameElem.value = getHeadline(taskController.getSelectedTask());
                }
            }

            /**
             * Check whether the activity has a custom name
             *
             * @return {boolean} True, if there is no headline with that name in all tasks
             */
            function isCustomName() {
                if (nameElem.value.length == 0) {
                    return false;
                }
                return !getAllHeadlines().includes(nameElem.value);
            }

            /**
             * Get the task's headline for the currently selected language
             * @param {Object} task
             * @returns  {string} the headline
             */
            function getHeadline(task) {
                if (!task) {
                    return null;
                }
                for (var i in task.headline) {
                    var localHeadline = task.headline[i];
                    if (localHeadline.language == langController.getSelectedLanguage()) {
                        return localHeadline.name;
                    }
                }
                return null;
            }

            /**
             * Get all tasks that are available on all servers
             *
             * @return {Object} Array containing all available tasks
             */
            function getAllTasks() {
                var tasks = [];
                for (var i in serverController.getAllServers()) {
                    var server = serverController.getAllServers()[i];
                    for (var j in server.courses) {
                        var course = server.courses[j];
                        for (var m in course.tasks) {
                            var task = course.tasks[m];
                            tasks.push(task);
                        }
                    }
                }
                return tasks;
            }

            /**
             * Get all possible headlines in all languages
             * @returns {Object} Array containing all headlines
             */
            function getAllHeadlines() {
                var headlines = [];
                var tasks = getAllTasks();
                tasks.push(getPseudoTaskFromCourse(courseController.getSelectedCourse()));
                for (var i in tasks) {
                    var task = tasks[i];
                    for (var n in task.headline) {
                        headlines.push(task.headline[n].name);
                    }
                }
                var course = courseController.getSelectedCourse();
                for (var j in course.name) {
                    var name = course.name[j];
                    headlines.push(name.value);
                }
                return headlines;
            }

            /**
             * Add lanugage parameter to the task's link to display content in the selected language
             * @param {Object} task
             * @returns {string}
             */
            function getLocalizedLink(task) {
                return task.link + "?lang=" + langController.getSelectedLanguage();
            }

            /**
             * Add a new option to the 'Problem' drop down menu
             * @param {Object} task
             */
            function addTaskOption(task) {
                if (getHeadline(task) !== null) {
                    var optionTask = document.createElement("option");
                    optionTask.setAttribute("value", getLocalizedLink(task));
                    optionTask.text = getHeadline(task);
                    taskDropDown.append(optionTask);
                }
            }

            /**
             * User can chose to select an entire course instead of a single problem.
             * If they do so, we simply add a pseudo problem linking to the courses overview page
             * @param {Object} course
             */
            function addPseudoTaskOption(course) {
                var task = getPseudoTaskFromCourse(course);
                var optionTask = document.createElement("option");
                optionTask.setAttribute("value", getLocalizedLink(task));
                optionTask.text = getHeadline(task);
                taskDropDown.append(optionTask);
            }

            /**
             * Get a task that links to a course's overview page
             * @param {Object} course
             * @returns {Object} task
             */
            function getPseudoTaskFromCourse(course) {
                var headline = [];
                for (var i in course.name) {
                    var name = course.name[i];
                    headline.push({
                        "name": name.value,
                        "language": name.language
                    });
                }
                return {
                    "link": course.link,
                    "headline": headline
                };
            }

            /**
             * Returns true, if the user has chosen to select the entire course instead of a single problem
             * @returns {boolean}
             */
            function useCompleteCourse() {
                return useCompleteCourseElem.checked;
            }

            return {
                init: function(isEdit) {
                    updateName();
                    taskDropDown.onchange = function() {
                        updateName();
                    };
                    useCompleteCourseElem.onchange = function() {
                        taskController.updateOptions();
                    };
                    taskController.updateOptions(isEdit ?
                        taskDropDown.options[taskDropDown.selectedIndex].getAttribute('value') : undefined
                    );
                },
                getSelectedTask: function() {
                    var selectedLink = taskDropDown.options[taskDropDown.selectedIndex] ==
                        undefined ? undefined : taskDropDown.options[taskDropDown.selectedIndex].getAttribute('value');
                    var course = courseController.getSelectedCourse();
                    var tasks = course.tasks.slice();
                    tasks.push(getPseudoTaskFromCourse(course));
                    for (var i in tasks) {
                        var task = tasks[i];
                        if (selectedLink == getLocalizedLink(task)) {
                            return task;
                        }
                    }
                    return null;
                },
                disable: function() {
                    taskDropDown.disabled = true;
                    removeChildElems(taskDropDown);
                },
                updateOptions: function(selectTaskByLink) {
                    removeChildElems(taskDropDown);
                    taskDropDown.selectedIndex = 0;
                    if (useCompleteCourse()) {
                        addPseudoTaskOption(courseController.getSelectedCourse());
                    } else {
                        var tasks = courseController.getSelectedCourse().tasks;
                        for (var i in tasks) {
                            var task = tasks[i];
                            addTaskOption(task);
                            if (selectTaskByLink === getLocalizedLink(task)) {
                                taskDropDown.selectedIndex = taskDropDown.childElementCount - 1;
                            }
                        }
                    }
                    updateName();
                },
                useCompleteCourse: function() {
                    return useCompleteCourse();
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
            courseController.disable();
            langController.disable();
            taskController.disable();
            problemSelectorController.disable();
        }

        return {
            init: function(contextid, prbSelectorUrl, lang) {
                lmsSelectorUrl = prbSelectorUrl;
                systemLanguage = lang;
                var isEdit = document.getElementById("id_name").getAttribute('value');
                var serverStructure = JSON.parse(document.getElementsByName('mumie_server_structure')[0].value);
                if (isEdit && !serverConfigExists()) {
                    disableDropDownMenus('mumie_form_missing_server');
                } else if (!serverStructure.length) {
                    disableDropDownMenus('mumie_form_no_server_conf');
                } else {
                    serverController.init(serverStructure);
                    courseController.init(isEdit);
                    taskController.init(isEdit);
                    langController.init();
                    problemSelectorController.init();
                }
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