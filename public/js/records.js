/**
 * Jail And Arrest Records: admin form behaviour.
 *
 * Lives here rather than inline in Blade (house rule: markup in templates,
 * logic in classes, JS in public/js). Registered on `alpine:init` so it is
 * available before Alpine walks the DOM, regardless of script order.
 */
(function () {
    'use strict';

    document.addEventListener('alpine:init', function () {

        /**
         * The repeating charge rows on the booking form.
         *
         * A booking is rarely a single count, and squashing several charges
         * into one free-text field is what makes a disposition meaningless
         * later ("dismissed": which one?). So charges are real rows.
         */
        Alpine.data('chargeRows', function (initial, severities, defaultSeverity) {
            return {
                rows: (initial && initial.length ? initial : [blankRow(defaultSeverity)]),
                severities: severities || {},

                add: function () {
                    this.rows.push(blankRow(defaultSeverity));
                },

                remove: function (index) {
                    this.rows.splice(index, 1);
                    if (!this.rows.length) this.add();
                },

                moveUp: function (index) {
                    if (index === 0) return;
                    var row = this.rows.splice(index, 1)[0];
                    this.rows.splice(index - 1, 0, row);
                },

                moveDown: function (index) {
                    if (index >= this.rows.length - 1) return;
                    var row = this.rows.splice(index, 1)[0];
                    this.rows.splice(index + 1, 0, row);
                },
            };
        });

        /**
         * Publication panel. Watches the age field so the juvenile block is
         * visible while typing rather than only after a failed save. The
         * server enforces it regardless; this is courtesy, not the guardrail.
         */
        Alpine.data('publishPanel', function (published, age, minimumAge) {
            return {
                published: !!published,
                age: age === null || age === '' ? null : Number(age),
                minimumAge: Number(minimumAge) || 18,

                get isJuvenile() {
                    return this.age !== null && !isNaN(this.age) && this.age < this.minimumAge;
                },

                get blocked() {
                    return this.isJuvenile;
                },

                syncAge: function (value) {
                    this.age = value === '' ? null : Number(value);
                    if (this.isJuvenile) this.published = false;
                },
            };
        });
    });

    function blankRow(defaultSeverity) {
        return {
            description: '',
            statute: '',
            severity: defaultSeverity || 'misdemeanor',
            counts: 1,
        };
    }
})();
