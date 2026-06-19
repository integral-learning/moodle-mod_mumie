define([
    'jquery',
    'core/str',
    'core/modal_save_cancel',
    'core/modal_events',
    'core/fragment',
    'core/ajax',
    'core_form/changechecker',
], function($, Str, ModalSaveCancel, ModalEvents, Fragment, Ajax, FormChangeChecker) {

        var MumieDueDate = function(selector, contextid, formdata) {
            this.contextid = contextid;
            this.init(selector, formdata);
        };

        MumieDueDate.prototype.modal = null;

        MumieDueDate.prototype.contextid = -1;

        MumieDueDate.prototype.init = function(selector, formdata) {
            $(selector).on('click', function(e) {
                e.preventDefault();
                return Str.get_string('mumie_duedate_form', 'mod_mumie').then(function(title) {
                    return ModalSaveCancel.create({
                        title: title,
                        body: this.getBody(formdata),
                        large: true,
                        removeOnClose: true,
                    });
                }.bind(this)).then(function(modal) {
                    this.modal = modal;

                    // We catch the modal save event, and use it to submit the form inside the modal.
                    // Triggering a form submission will give JS validation scripts a chance to check for errors.
                    modal.getRoot().on(ModalEvents.save, this.submitForm.bind(this));
                    // We also catch the form submit event and use it to submit the form with ajax.
                    modal.getRoot().on('submit', 'form', this.submitFormAjax.bind(this));

                    modal.show();
                    return modal;
                }.bind(this));
            }.bind(this));
        };

        /**
         * @method getBody
         * @private
         * @param {Object} formdata
         * @return {Promise}
         */
        MumieDueDate.prototype.getBody = function(formdata) {
            if (typeof formdata === "undefined") {
                formdata = {};
            }
            // Get the content of the modal.
            var params = {
                jsonformdata: JSON.stringify(formdata)
            };
            return Fragment.loadFragment('mod_mumie', 'new_duedate_form', this.contextid, params);
        };

        /**
         * @method handleFormSubmissionResponse
         * @private
         */
        MumieDueDate.prototype.handleFormSubmissionResponse = function() {
            this.modal.hide();
            FormChangeChecker.resetAllFormDirtyStates();
            document.location.reload();
        };

        /**
         * @method handleFormSubmissionFailure
         * @private
         * @param {Object} data
         */
        MumieDueDate.prototype.handleFormSubmissionFailure = function(data) {
            // Oh noes! Epic fail :(
            // Ah wait - this is normal. We need to re-display the form with errors!
            this.modal.setBody(this.getBody(data));
        };

        /**
         * Private method
         *
         * @method submitFormAjax
         * @private
         * @param {Event} e Form submission event.
         */
        MumieDueDate.prototype.submitFormAjax = function(e) {
            // We don't want to do a real form submission.
            e.preventDefault();

            // Convert all the form elements values to a serialized string.
            var formData = this.modal.getRoot().find('form').serialize();
            // Now we can continue...
            Ajax.call([{
                methodname: 'mod_mumie_submit_mumieduedate_form',
                args: {
                    contextid: this.contextid,
                    jsonformdata: JSON.stringify(formData)
                },
                done: this.handleFormSubmissionResponse.bind(this, formData),
                fail: this.handleFormSubmissionFailure.bind(this, formData)
            }]);
        };

        /**
         * This triggers a form submission, so that any mform elements can do final tricks before the form submission is processed.
         *
         * @method submitForm
         * @param {Event} e Form submission event.
         * @private
         */
        MumieDueDate.prototype.submitForm = function(e) {
            e.preventDefault();
            this.modal.getRoot().find('form').submit();
        };

        return /** @alias module:mod_mumie/newduedate */ {
            // Public variables and functions.
            /**
             * Attach event listeners to initialize this module.
             *
             * @method init
             * @param {string} selector The CSS selector used to find nodes that will trigger this module.
             * @param {int} contextid The contextid for the course.
             * @param {Object} formdata
             * @return {Promise}
             */
            init: function(selector, contextid, formdata) {
                return new MumieDueDate(selector, contextid, formdata);
            }
        };

    });