define(['jquery', 'core/templates', 'core/modal_factory', 'mod_mumie/duedate_form'],
    function($) {
        return {
            init: function(contextid) {
                setAddDuedateListeners(contextid);
                setEditDuedateListeners(contextid);
            }
        };

        /**
         * Set click listener to add a server button
         * @param {number} contextid
         */
        function setAddDuedateListeners(contextid) {
            const addBtns = $(".mumie_duedate_add_btn");

            addBtns.each(function(i) {
                const btn = addBtns[i];
                const data = JSON.parse(btn.children[1].textContent);
                const formdata = "&_qf__duedate_form=1&userid=" + data.userid + "&mumie=" + data.mumie;
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
            const editBtns = $(".mumie_duedate_edit_btn");

            editBtns.each(function(i) {
                const btn = editBtns[i];
                const data = JSON.parse(btn.children[1].textContent);
                const formdata = "id=" + data.id +
                    "&_qf__duedate_form=1&userid=" + data.userid + "&mumie=" + data.mumie + "&duedate=" + data.duedate;
                require(['mod_mumie/duedate_form'], function(MumieDueDate) {
                    MumieDueDate.init(btn, contextid, formdata);
                });
            });
        }
    });