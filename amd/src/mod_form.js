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
                        taskController.updateCompleteCourseVisibility();
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
                        taskController.setSelection(importObj.link + '?lang=' + importObj.language);
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
                                + '&problemLang='
                                + langController.getSelectedLanguage()
                                + '&origin=' + encodeURIComponent(window.location.origin)
                                + '&uiLang=' + systemLanguage
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
                        taskController.updateCompleteCourseVisibility();
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
            var languageDropDown = document.getElementById("id_language_dropdown");
            var languageElem = document.getElementById("id_language");

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

            /**
             * Update the selection of the drop down language with the given lang
             * @param {string} lang
             */
            function setDropDownLanguage(lang) {
                for (var i in languageDropDown.options) {
                    var option = languageDropDown.options[i];
                    if (option.value == lang) {
                        languageDropDown.selectedIndex = i;
                        courseController.updateOptions();
                        return;
                    }
                }
            }

            /**
             * Check if the given language exists in the currently selected course.
             * @param {string} lang
             * @returns {boolean} Whether the language exists
             */
            function languageExists(lang) {
                return courseController.getSelectedCourse().languages.includes(lang);
            }
            return {
                init: function() {
                    languageDropDown.onchange = function() {
                        languageElem.value = languageDropDown.options[languageDropDown.selectedIndex].text;
                        courseController.updateOptions();
                        taskController.setSelection();
                    };
                    langController.updateOptions(langController.getSelectedLanguage());
                },
                getSelectedLanguage: function() {
                    return languageElem.value;
                },
                setLanguage: function(lang) {
                    if (!languageExists(lang)) {
                        throw new Error("Selected language not available");
                    }
                    languageElem.value = lang;
                    setDropDownLanguage(lang);
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
                    }
                    setDropDownLanguage(currentLang);
                }
            };
        })();

        var taskController = (function() {
            var taskSelectionInput = document.getElementsByName("taskurl")[0];
            var nameElem = document.getElementById("id_name");
            var taskDisplayElement = document.getElementById("id_task_display_element");
            var useCompleteCourseElem = document.getElementById("id_mumie_complete_course");

            /**
             * Update the activity's name in the input field
             */
            function updateName() {
                if (!isCustomName()) {
                    nameElem.value = getHeadline(taskController.getSelectedTask());
                }
                taskDisplayElement.value = getHeadline(taskController.getSelectedTask());
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
             * Add language parameter to the task's link to display content in the selected language
             * @param {Object} task
             * @returns {string}
             */
            function getLocalizedLinkFromTask(task) {
                return getLocalizedLink(task.link);
            }

            /**
             * Add language parameter to link
             * @param {string} link
             * @returns {string}
             */
            function getLocalizedLink(link) {
                return link + "?lang=" + langController.getSelectedLanguage();
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
                init: function() {
                    taskController.updateCompleteCourseVisibility();
                    updateName();
                    useCompleteCourseElem.onchange = function() {
                        if (useCompleteCourse()) {
                            var localizedLink = getLocalizedLink(courseController.getSelectedCourse().link);
                            taskController.setSelection(localizedLink);
                        } else {
                            taskController.setSelection(null);
                        }
                        // Circumvent moodle bug that ignores "disabled" if visibility has changed.
                        taskDisplayElement.disabled = "1";
                    };
                },
                getSelectedTask: function() {
                    var selectedLink = taskSelectionInput.value;
                    var course = courseController.getSelectedCourse();
                    var tasks = course.tasks.slice();
                    tasks.push(getPseudoTaskFromCourse(course));
                    for (var i in tasks) {
                        var task = tasks[i];
                        if (selectedLink == getLocalizedLinkFromTask(task)) {
                            return task;
                        }
                    }
                    return null;
                },
                setSelection: function(newSelection) {
                    if (!newSelection && useCompleteCourse()) {
                        newSelection = getLocalizedLink(courseController.getSelectedCourse().link);
                    }
                    taskSelectionInput.value = newSelection;
                    updateName();
                },
                useCompleteCourse: function() {
                    return useCompleteCourse();
                },
                updateCompleteCourseVisibility: function() {
                    var visible = courseController.getSelectedCourse().link;
                    useCompleteCourseElem.parentElement.parentElement.parentElement.style =
                     visible ? "display:block" : "display:none";
                }
            };
        })();


        var multiTaskController = (function(){
            var taskSelecetionInputs = document.getElementsByName("blub0");
            window.console.log(taskSelecetionInputs);
            var selectedTasks = document.getElementsByName("mumie_selected_tasks")[0];
            var selectedTaskIds = [];
            function addTask(task, array) {
                array.push(task);
            }

            function removeTask(task, array) {
            const index = array.indexOf(task);
            if (index > -1) {
                array.splice(index, 1);
            }
            }

            function updateselectedTasks(selectedTasks1,task, updateArray, array) {
                updateArray(task, array);
                selectedTasks1.value = array.toString();
            }

            return {
                init: function() {
                    for (let taskSelectionInput of taskSelecetionInputs){
                        updateselectedTasks(selectedTasks,taskSelectionInput.getAttribute('value'),addTask,selectedTaskIds);
                        taskSelectionInput.onchange = function(){
                            if(this.getAttribute('checked')=='checked'){
                                updateselectedTasks(selectedTasks,this.getAttribute('value'),removeTask,selectedTaskIds);
                                this.setAttribute('checked','unchecked');
                                window.console.log(this);
                            }
                            else{
                                updateselectedTasks(selectedTasks,this.getAttribute('value'),addTask,selectedTaskIds);
                                this.setAttribute('checked','checked');
                                window.console.log(this);
                            }
                        };
                    }
                }
            };

        })();

        // var multiTaskController2 = (function(){
        //     var taskPropertySelecetionInputs = document.getElementsByName("blub1");
        //     var selectedTaskProperties = document.getElementsByName("mumie_selected_task_properties")[0];
        //     var selectedTaskProp = [];
        //     window.console.log(taskPropertySelecetionInputs);
        //     function addTask(task, array) {
        //         array.push(task);
        //     }

        //     function removeTask(task, array) {
        //     const index = array.indexOf(task);
        //     if (index > -1) {
        //         array.splice(index, 1);
        //     }
        //     }

        //     function updateselectedTasks(selectedTasks1,task, updateArray, array) {
        //         updateArray(task, array);
        //         selectedTasks1.value = array.toString();
        //     }

        //     return {
        //         init: function() {
        //             for (let taskPropertySelecetionInput of taskPropertySelecetionInputs){
        //                 window.console.log("test2");
        //                 updateselectedTasks(selectedTaskProperties,taskPropertySelecetionInput
        //                     .getAttribute('value'),addTask,selectedTaskProp);
        //                 taskPropertySelecetionInput.onchange = function(){
        //                     if(this.getAttribute('checked')=='checked'){
        //                     updateselectedTasks(selectedTaskProperties,this.getAttribute('value'),removeTask,selectedTaskProp);
        //                         this.setAttribute('checked','unchecked');
        //                         window.console.log(this);
        //                     }
        //                     else{
        //                         updateselectedTasks(selectedTaskProperties,this.getAttribute('value'),addTask,selectedTaskProp);
        //                         this.setAttribute('checked','checked');
        //                         window.console.log(this);
        //                     }
        //                 };
        //             }
        //         }
        //     };

        // })();

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
                    multiTaskController.init();
                    // multiTaskController2.init();
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