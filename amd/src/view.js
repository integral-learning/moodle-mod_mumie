define(['jquery', 'core/templates', 'core/modal_factory', 'mod_mumie/duedate_form'],
    function($) {
        return {
            init: function(contextid) {
                setAddDuedateListeners(contextid);
                setEditDuedateListeners(contextid);
            }
        };

        /**
         * Set click listener to add server button
         * @param {number} contextid
         */
        function setAddDuedateListeners(contextid) {
            var addBtns = $(".mumie_duedate_add_btn");

            addBtns.each(function(i) {
                var btn = addBtns[i];
                var data = JSON.parse(btn.children[1].textContent);
                var formdata = "&_qf__duedate_form=1&userid=" + data.userid + "&mumie=" + data.mumie;
                require(['mod_mumie/duedate_form'], function(MumieDueDate) {
                    MumieDueDate.init(btn, contextid, formdata);
                });
            });
        }

        /**
         * Set click listeners for the edit buttons
         * @param {number} contextid
         */
        function setEditDuedateListeners(contextid) {
            var editBtns = $(".mumie_duedate_edit_btn");

            editBtns.each(function(i) {
                var btn = editBtns[i];
                var data = JSON.parse(btn.children[1].textContent);
                var formdata = "id=" + data.id +
                    "&_qf__duedate_form=1&userid=" + data.userid + "&mumie=" + data.mumie + "&duedate=" + data.duedate;
                    require(['mod_mumie/duedate_form'], function(MumieDueDate) {
                    MumieDueDate.init(btn, contextid, formdata);
                });
            });
        }
    });