define(['jquery', 'core/templates', 'core/modal_factory', 'auth_mumie/mumie_server_config', 'core/ajax'],
    function () {
        var addServerButton = document.getElementById("id_add_server_button");
        var serverDropDown = document.getElementById("id_server");
        var courseDropDown = document.getElementById("id_mumie_course");
        var languageDropDown = document.getElementById("id_language");
        var taskDropDown = document.getElementById("id_taskurl");
        var nameElem = document.getElementById("id_name");
        var coursefileElem = document.getElementsByName("mumie_coursefile")[0];
        var missingConfig = document.getElementsByName("mumie_missing_config")[0];
        var availableCourses = {};
        return {
            init: function (contextid) {

                var isEdit = nameElem.getAttribute('value') !== null && nameElem.getAttribute("value").length > 0;

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
                    serverDropDown.disabled = true;
                    courseDropDown.disabled = true;
                    languageDropDown.disabled = true;
                    taskDropDown.disabled = true;
                    removeChildElems(courseDropDown);
                    removeChildElems(serverDropDown);
                    removeChildElems(languageDropDown);
                    removeChildElems(taskDropDown);
                } else {
                    require(['core/ajax'], function (ajax) {
                        ajax.call([{
                            methodname: 'auth_mumie_get_available_courses',
                            args: {
                                contextid: contextid
                            },
                            done: function (res) {
                                availableCourses = JSON.parse(res);

                                if (isEdit) {
                                    var exName = nameElem.getAttribute("value");
                                    var exCourse = courseDropDown.options[courseDropDown.selectedIndex].text;
                                    var exTask = taskDropDown.options[taskDropDown.selectedIndex].getAttribute("value");
                                    var exLanguage = languageDropDown.options[languageDropDown.selectedIndex].text;
                                    updateCoursesDropDownOptions(exCourse);
                                    updateLanguageDropDownOptions(exLanguage);
                                    updateTaskDropDownOptions(exTask);
                                    setSelectionListeners();
                                    updateCoursefilePath();
                                    nameElem.value = exName;

                                } else {
                                    updateCoursesDropDownOptions();
                                    updateLanguageDropDownOptions();
                                    updateTaskDropDownOptions();
                                    setSelectionListeners();
                                    updateCoursefilePath();
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

        function updateCoursesDropDownOptions(displayCourse) {
            if (Object.keys(availableCourses).length == 0) {
                return;
            }
            var selectedServerName = serverDropDown.options[serverDropDown.selectedIndex].text;
            var possibleCourses = availableCourses[selectedServerName]["courses"];

            removeChildElems(courseDropDown);

            for (var i in possibleCourses) {
                var course = possibleCourses[i];
                var optionCourse = document.createElement("option");
                optionCourse.setAttribute("value", course.name);
                optionCourse.text = course.name;
                courseDropDown.append(optionCourse);

                if (course.name === displayCourse) {
                    courseDropDown.selectedIndex = i;
                }
            }
        }

        function updateLanguageDropDownOptions(displayLang) {
            if (Object.keys(availableCourses).length == 0) {
                return;
            }

            removeChildElems(languageDropDown);

            var selectedCourseName = courseDropDown.options[courseDropDown.selectedIndex].text;
            var selectedServerName = serverDropDown.options[serverDropDown.selectedIndex].text;

            for (var lang in getAvailableLanguages(selectedServerName, selectedCourseName)) {
                var optionLang = document.createElement("option");
                optionLang.setAttribute("value", lang);
                optionLang.text = lang;
                languageDropDown.append(optionLang);

                if (lang === displayLang) {
                    languageDropDown.selectedIndex = languageDropDown.options.length - 1;
                }
            }
        }

        function updateTaskDropDownOptions(displayTask) {
            if (Object.keys(availableCourses).length == 0) {
                return;
            }
            var selectedCourseName = courseDropDown.options[courseDropDown.selectedIndex].text;
            var selectedServerName = serverDropDown.options[serverDropDown.selectedIndex].text;
            var selectedLanguage = languageDropDown.options[languageDropDown.selectedIndex].text;

            var possibleTasks = availableCourses[selectedServerName]["courses"].find(function (course) {
                return course.name === selectedCourseName;
            }).tasks;

            removeChildElems(taskDropDown);

            for (var i in possibleTasks) {
                var link = possibleTasks[i]['link'] + '?lang=' + selectedLanguage;
                var name = null;
                var headlines = possibleTasks[i]['headline'];
                for (var j in headlines) {
                    var headline = headlines[j];
                    if (headline['language'] === selectedLanguage) {
                        name = headline['name'];
                        break;
                    }
                }
                var optionTask = document.createElement("option");
                optionTask.setAttribute("value", link);
                optionTask.text = name;
                if (name !== null) {
                    taskDropDown.append(optionTask);
                }

                if (link === displayTask) {
                    taskDropDown.selectedIndex = i;
                }
            }
            updateName();
        }

        function removeChildElems(elem) {
            while (elem.firstChild) {
                elem.removeChild(elem.firstChild);
            }
        }

        function updateName() {
            var selectedTaskName = taskDropDown.options[taskDropDown.selectedIndex].text;
            nameElem.value = selectedTaskName;
        }

        function updateCoursefilePath() {
            if (Object.keys(availableCourses).length == 0) {
                return;
            }
            var selServerName = serverDropDown.options[serverDropDown.selectedIndex].text;
            coursefileElem.value = availableCourses[selServerName]["courses"][courseDropDown.selectedIndex]["pathToCourseFile"];
        }

        function setSelectionListeners() {
            serverDropDown.onchange = function () {
                updateCoursesDropDownOptions();
                updateLanguageDropDownOptions(languageDropDown.options[languageDropDown.selectedIndex].text);
                updateTaskDropDownOptions();
                updateCoursefilePath();
            };

            courseDropDown.onchange = function () {
                updateLanguageDropDownOptions(languageDropDown.options[languageDropDown.selectedIndex].text);
                updateTaskDropDownOptions();
                updateCoursefilePath();
            };

            languageDropDown.onchange = function () {
                updateTaskDropDownOptions();
            };

            taskDropDown.onchange = function () {
                updateName();
            };
        }

        function serverConfigExists() {
            return missingConfig.getAttribute("value") === "";
        }

        function getAvailableLanguages(serverName, courseName) {

            var availableLang = [];
            var possibleTasks = availableCourses[serverName]["courses"].find(function (course) {
                return course.name === courseName;
            }).tasks;

            for (var i in possibleTasks) {
                var task = possibleTasks[i];
                for (var j in task['headline']) {
                    var headline = task['headline'][j];
                    availableLang[headline['language']] = headline['language'];
                }
            }
            return availableLang;
        }
    });