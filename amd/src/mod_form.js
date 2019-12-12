define(['jquery', 'core/templates', 'core/modal_factory', 'auth_mumie/mumie_server_config', 'core/ajax'],
    function () {
        var addServerButton = document.getElementById("id_add_server_button");
        var missingConfig = document.getElementsByName("mumie_missing_config")[0];

        var serverController = (function () {
            var serverStructure;
            var serverDropDown = document.getElementById("id_server");

            return {
                init: function (structure) {
                    serverStructure = structure;
                    serverDropDown.onchange = function () {
                        courseController.updateOptions();
                        langController.updateOptions();
                        filterController.updateOptions();
                        taskController.updateOptions();
                    };
                },
                getSelectedServer: function () {
                    var selectedServerName = serverDropDown.options[serverDropDown.selectedIndex].text;

                    for (var i in serverStructure) {
                        var server = serverStructure[i];
                        if (server.name == selectedServerName) {
                            return server;
                        }
                    }
                },
                setDropDownListeners: function () {

                },
                disable: function () {
                    serverDropDown.disabled = true;
                    removeChildElems(serverDropDown);
                }

            };
        })();

        var courseController = (function () {
            var courseDropDown = document.getElementById("id_mumie_course");
            var coursefileElem = document.getElementsByName("mumie_coursefile")[0];

            function addOptionForCourse(course) {
                var optionCourse = document.createElement("option");
                optionCourse.setAttribute("value", course.name);
                optionCourse.text = course.name;
                courseDropDown.append(optionCourse);
            }

            function updateCoursefilePath() {
                coursefileElem.value = courseController.getSelectedCourse().coursefile;
            }

            return {
                init: function () {
                    courseDropDown.onchange = function () {
                        langController.updateOptions();
                        filterController.updateOptions();
                        taskController.updateOptions();
                    };
                    courseController.updateOptions();
                },
                getSelectedCourse: function () {
                    var selectedCourseName = courseDropDown.options[courseDropDown.selectedIndex].text;
                    var courses = serverController.getSelectedServer().courses;
                    for (var i in courses) {
                        var course = courses[i];
                        if (course.name == selectedCourseName) {
                            return course;
                        }
                    }
                },
                setDropDownListeners: function () {

                },
                disable: function () {
                    courseDropDown.disabled = true;
                    removeChildElems(courseDropDown);
                },
                updateOptions: function (selectedCourseFile) {
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

        var langController = (function () {
            var languageDropDown = document.getElementById("id_language");

            function addLanguageOption(lang) {
                var optionLang = document.createElement("option");
                optionLang.setAttribute("value", lang);
                optionLang.text = lang;
                languageDropDown.append(optionLang);
            }
            return {
                init: function () {

                    languageDropDown.onchange = function () {
                        taskController.updateOptions();
                    };
                    langController.updateOptions();
                },
                getSelectedLanguage: function () {
                    return languageDropDown.options[languageDropDown.selectedIndex].text;
                },
                disable: function () {
                    languageDropDown.disabled = true;
                    removeChildElems(languageDropDown);
                },
                updateOptions: function () {
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

        var taskController = (function () {
            var taskDropDown = document.getElementById("id_taskurl");
            var nameElem = document.getElementById("id_name");

            function updateName() {
                nameElem.value = getHeadline(taskController.getSelectedTask());
            }

            function getHeadline(task) {
                for (var i in task.headline) {
                    var localHeadline = task.headline[i];
                    if (localHeadline.language == langController.getSelectedLanguage()) {
                        return localHeadline.name;
                    }
                }
                return null;
            }

            function getLocalizedLink(task) {
                return task.link + "?lang=" + langController.getSelectedLanguage();
            }

            function addTaskOption(task) {
                if (getHeadline(task) !== null) {
                    var optionTask = document.createElement("option");
                    optionTask.setAttribute("value", getLocalizedLink(task));
                    optionTask.text = getHeadline(task);
                    taskDropDown.append(optionTask);
                }
            }

            return {
                init: function () {
                    updateName();
                    taskDropDown.onchange = function () {
                        updateName();
                    };
                    taskController.updateOptions();
                },
                getSelectedTask: function () {
                    var selectedLink = taskDropDown.options[taskDropDown.selectedIndex].getAttribute('value');
                    var tasks = courseController.getSelectedCourse().tasks;
                    for (var i in tasks) {
                        var task = tasks[i];
                        if (selectedLink == getLocalizedLink(task)) {
                            return task;
                        }
                    }
                },
                disable: function () {
                    taskDropDown.disabled = true;
                    removeChildElems(taskDropDown);
                },
                updateOptions: function (selectTaskByLink) {
                    removeChildElems(taskDropDown);
                    taskDropDown.selectedIndex = 0;
                    var tasks = courseController.getSelectedCourse().tasks;
                    for (var i in tasks) {
                        var task = tasks[i];
                        addTaskOption(task);
                        if (selectTaskByLink === getLocalizedLink(task)) {
                            taskDropDown.selectedIndex = taskDropDown.childElementCount - 1;
                        }
                    }
                    updateName();
                },
            };
        })();

        var filterController = (function () {
            var filterWrapper = document.getElementById("mumie_filter_wrapper");

            function addFilter(tag) {
                var filter = document.createElement('div');
                filter.classList.add('row', 'fitem', 'form-group');

                var label = document.createElement('label');
                label.innerText = tag.name;
                label.classList.add('col-md-3');
                filter.appendChild(label);
                filter.appendChild(createSelectionBox(tag));

                filterWrapper.appendChild(filter);

            }

            function createSelectionBox(tag) {
                var selectionBox = document.createElement('div');
                selectionBox.classList.add('col-md-9', 'felement', 'mumie_selection_box');
                for (var i in tag.values) {
                    var inputWrapper = document.createElement('div');

                    var value = tag.values[i];
                    var checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.value = value;

                    var label = document.createElement('label');
                    label.innerText = value;
                    label.style = "padding-left: 5px";
                    inputWrapper.appendChild(checkbox);
                    inputWrapper.appendChild(label);
                    selectionBox.appendChild(inputWrapper);
                }
                return selectionBox;
            }

            return {
                init: function () {
                    this.updateOptions();
                },
                updateOptions: function () {
                    var tags = courseController.getSelectedCourse().tags;
                    removeChildElems(filterWrapper);
                    for (var i in tags) {
                        var tag = tags[i];
                        addFilter(tag);
                    }
                }
            };

        })();

        return {
            init: function (contextid) {
                var isEdit = document.getElementById("id_name").getAttribute('value');

                if (isEdit && !serverConfigExists()) {
                    require(['core/str', "core/notification"], function (str, notification) {
                        str.get_strings([{
                            'key': 'mumie_form_missing_server',
                            component: 'mod_mumie'
                        }, ]).done(function (s) {
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
                } else {
                    require(['core/ajax'], function (ajax) {
                        ajax.call([{
                            methodname: 'auth_mumie_get_server_structure',
                            args: {
                                contextid: contextid
                            },
                            done: function (serverStructure) {

                                serverController.init(JSON.parse(serverStructure));
                                courseController.init();
                                taskController.init();
                                langController.init();
                                filterController.init();

                                if (isEdit) {
                                    
                                    var exName = nameElem.getAttribute("value");
                                    var exCourse = courseDropDown.options[courseDropDown.selectedIndex].text;
                                    var exTask = taskDropDown.options[taskDropDown.selectedIndex].getAttribute("value");
                                    var exLanguage = languageDropDown.options[languageDropDown.selectedIndex].text;
                                    updateCoursesDropDownOptions(exCourse);
                                    updateLanguageDropDownOptions(exLanguage);
                                    updateTaskDropDownOptions(exTask);
                                    nameElem.value = exName;
                                    
                                }
                            },
                            fail: function (ex) {
                                alert(JSON.stringify(ex));
                            }
                        }]);
                    });
                }

                require(['auth_mumie/mumie_server_config'], function (MumieServer) {
                    MumieServer.init(addServerButton, contextid);
                });
            }
        };

        function removeChildElems(elem) {
            while (elem.firstChild) {
                elem.removeChild(elem.firstChild);
            }
        }

        function serverConfigExists() {
            return document.getElementsByName("mumie_missing_config")[0].getAttribute("value") === "";
        }
    });