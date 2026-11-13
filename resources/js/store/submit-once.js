export default () => ({
    submitting: false,

    submit(event) {
        if (this.submitting) {
            event.preventDefault();

            return;
        }

        this.submitting = true;
    },
});
