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
                init: function (isEdit) {
                    courseDropDown.onchange = function () {
                        updateCoursefilePath();
                        langController.updateOptions();
                        filterController.updateOptions();
                        taskController.updateOptions();
                    };
                    courseController.updateOptions(isEdit ? coursefileElem.value : false);
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
                init: function (isEdit) {
                    updateName();
                    taskDropDown.onchange = function () {
                        updateName();
                    };
                    taskController.updateOptions(isEdit ?
                        taskDropDown.options[taskDropDown.selectedIndex].getAttribute('value') : undefined
                    );
                },
                getSelectedTask: function () {
                    var selectedLink = taskDropDown.options[taskDropDown.selectedIndex] == undefined ?
                        undefined : taskDropDown.options[taskDropDown.selectedIndex].getAttribute('value');
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
                    var tasks = filterController.filterTasks(courseController.getSelectedCourse().tasks);
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
            var filterSection = document.getElementById('mumie_filter_section');
            var filterWrapper = document.getElementById("mumie_filter_wrapper");
            var filterSectionHeader = document.getElementById('mumie_filter_header');

            var selectedTags = [];

            function addFilter(tag) {
                var filter = document.createElement('div');
                filter.classList.add('row', 'fitem', 'form-group', 'mumie-filter');

                var selectionBox = createSelectionBox(tag);

                var label = document.createElement('label');
                label.innerHTML = '<i class="fa fa-caret-down mumie-icon"></i>' + tag.name;
                label.classList.add('col-md-3', 'mumie-collapsable');
                label.onclick = function () {
                    toggleVisibility(selectionBox);
                };
                filter.appendChild(label);
                filter.appendChild(selectionBox);
                filterWrapper.appendChild(filter);


            }

            function createSelectionBox(tag) {
                var selectionBox = document.createElement('div');
                selectionBox.classList.add('col-md-9', 'felement', 'mumie_selection_box');
                for (var i in tag.values) {
                    selectedTags[tag.name] = [];
                    var inputWrapper = document.createElement('div');
                    inputWrapper.classList.add('mumie_input_wrapper');

                    var value = tag.values[i];
                    var checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.value = value;
                    setCheckboxListener(tag, checkbox);

                    var label = document.createElement('label');
                    label.innerText = value;
                    label.style = "padding-left: 5px";
                    inputWrapper.appendChild(checkbox);
                    inputWrapper.appendChild(label);
                    selectionBox.appendChild(inputWrapper);
                }
                return selectionBox;
            }

            function setCheckboxListener(tag, checkbox) {
                checkbox.onclick = function () {
                    if (!checkbox.checked) {
                        var update = [];
                        for (var i in selectedTags[tag.name]) {
                            var value = selectedTags[tag.name][i];
                            if (value != checkbox.value) {
                                update.push(value);
                            }
                        }
                        selectedTags[tag.name] = update;
                    } else {
                        selectedTags[tag.name].push(checkbox.value);
                    }
                    taskController.updateOptions();
                };
            }

            function toggleVisibility(elem) {
                elem.toggleAttribute('hidden');
            }

            function filterTasks(tasks, filterSelection) {
                var filteredTasks = [];
                for (var i in tasks) {
                    var task = tasks[i];
                    if (filterTask(task, filterSelection)) {
                        filteredTasks.push(task);
                    }
                }
                return filteredTasks;
            }

            function filterTask(task, filterSelection) {
                var obj = [];
                for (i in task.tags) {
                    var tag = task.tags[i];
                    obj[tag.name] = tag.values;
                }

                for (var i in Object.keys(filterSelection)) {
                    var tagName = Object.keys(filterSelection)[i];
                    if (filterSelection[tagName].length == 0) {
                        continue;
                    }
                    if (!obj[tagName]) {
                        return false;
                    }
                    if (!haveCommonEntry(filterSelection[tagName], obj[tagName])) {
                        return false;
                    }
                }
                return true;
            }

            function haveCommonEntry(array1, array2) {
                if (!Array.isArray(array1) || !Array.isArray(array2)) {
                    return false;
                }
                for (var i = 0; i < array1.length; i++) {
                    if (array2.includes(array1[i])) {
                        return true;
                    }
                }
                return false;
            }

            return {
                init: function () {
                    this.updateOptions();
                    filterSectionHeader.onclick = function () {
                        toggleVisibility(filterWrapper);
                    };
                },
                updateOptions: function () {
                    var tags = courseController.getSelectedCourse().tags;
                    selectedTags = [];
                    if (tags.length > 0) {
                        filterSection.hidden = false;
                    } else {
                        filterSection.hidden = true;
                    }
                    removeChildElems(filterWrapper);
                    for (var i in tags) {
                        var tag = tags[i];
                        addFilter(tag);
                    }
                },
                filterTasks: function (tasks) {
                    return filterTasks(tasks, selectedTags);
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
                                courseController.init(isEdit);
                                taskController.init(isEdit);
                                langController.init();
                                filterController.init();
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