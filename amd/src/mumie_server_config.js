define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/fragment', 'core/ajax', 'core/yui'],
    function ($, Str, ModalFactory, ModalEvents, Fragment, Ajax, Y) {

        var MumieServer = function (selector, contextid, formdata) {
            this.contextid = contextid;
            this.init(selector, formdata);
        };

        MumieServer.prototype.modal = null;

        MumieServer.prototype.contextid = -1;

        MumieServer.prototype.init = function (selector, formdata) {
            var triggers = $(selector);
            return Str.get_string('mumie_form_server_config', 'mod_mumie').then(function (title) {
                // Create the modal.
                return ModalFactory.create({
                    type: ModalFactory.types.SAVE_CANCEL,
                    title: title,
                    body: this.getBody(formdata)
                }, triggers);
            }.bind(this)).then(function (modal) {
                // Keep a reference to the modal.
                this.modal = modal;

                // Forms are big, we want a big modal.
                this.modal.setLarge();

                // We want to reset the form every time it is opened.
                this.modal.getRoot().on(ModalEvents.hidden, function () {
                    this.modal.setBody(this.getBody(formdata));
                }.bind(this));

                // We catch the modal save event, and use it to submit the form inside the modal.
                // Triggering a form submission will give JS validation scripts a chance to check for errors.
                this.modal.getRoot().on(ModalEvents.save, this.submitForm.bind(this));
                // We also catch the form submit event and use it to submit the form with ajax.
                this.modal.getRoot().on('submit', 'form', this.submitFormAjax.bind(this));

                return this.modal;

            }.bind(this));
        };

        /**
         * @method getBody
         * @private
         * @return {Promise}
         */
        MumieServer.prototype.getBody = function (formdata) {
            if (typeof formdata === "undefined") {
                formdata = {};
            }
            // Get the content of the modal.
            var params = {
                jsonformdata: JSON.stringify(formdata)
            };
            return Fragment.loadFragment('mod_mumie', 'new_mumieserver_form', this.contextid, params);
        };

        /**
         * @method handleFormSubmissionResponse
         * @private
         * @return {Promise}
         */
        MumieServer.prototype.handleFormSubmissionResponse = function () {
            this.modal.hide();
            // We could trigger an event instead.
            // Yuk.
            Y.use('moodle-core-formchangechecker', function () {
                M.core_formchangechecker.reset_form_dirty_state();
            });

            document.location.reload();
        };

        /**
         * @method handleFormSubmissionFailure
         * @private
         * @return {Promise}
         */
        MumieServer.prototype.handleFormSubmissionFailure = function (data) {
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
        MumieServer.prototype.submitFormAjax = function (e) {
            // We don't want to do a real form submission.
            e.preventDefault();

            // Convert all the form elements values to a serialised string.
            var formData = this.modal.getRoot().find('form').serialize();
            // Now we can continue...
            Ajax.call([{
                methodname: 'mod_mumie_submit_mumieserver_form',
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
        MumieServer.prototype.submitForm = function (e) {
            e.preventDefault();
            this.modal.getRoot().find('form').submit();
        };

        return /** @alias module:mod_mumie/newserver */ {
            // Public variables and functions.
            /**
             * Attach event listeners to initialise this module.
             *
             * @method init
             * @param {string} selector The CSS selector used to find nodes that will trigger this module.
             * @param {int} contextid The contextid for the course.
             * @return {Promise}
             */
            init: function (selector, contextid, formdata) {
                return new MumieServer(selector, contextid, formdata);
            }
        };

    });