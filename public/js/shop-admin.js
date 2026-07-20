/**
 * ShopMGR merchant admin — small Alpine components and vanilla-JS helpers used
 * across the admin views. Loaded once from the layout (cache-busted via
 * asset_v()). No external chart/UI libraries — everything here is hand-rolled
 * SVG or plain DOM.
 */
(function () {
    'use strict';

    /* ------------------------------------------------------------------ *
     * Alpine components
     * ------------------------------------------------------------------ */
    document.addEventListener('alpine:init', function () {

        /**
         * Repeatable variant row editor for the product form's
         * "Variants & Pricing" tab. `initial` is the JSON-serialised list of
         * existing variants (edit) or an empty array (create); rows post as
         * variants[N][field] and are read verbatim by
         * ProductController::syncVariants().
         */
        Alpine.data('variantRepeater', function (initial) {
            return {
                rows: (initial && initial.length) ? initial : [blankVariant()],

                addRow: function () {
                    this.rows.push(blankVariant());
                },

                removeRow: function (index) {
                    if (this.rows.length > 1) {
                        this.rows.splice(index, 1);
                    }
                },

                /** Header label for a row: the option values, or a fallback. */
                rowLabel: function (row, index) {
                    var parts = [row.option1_value, row.option2_value, row.option3_value].filter(Boolean);
                    return parts.length ? parts.join(' / ') : ('Variant ' + (index + 1));
                },
            };
        });

        /**
         * Renders the dashboard's 30-day sales sparkline as a bare SVG
         * polyline/polygon — no charting library. `series` is
         * [{date, cents}, ...] from DashboardController::salesSeries().
         */
        Alpine.data('dashboardSparkline', function (series) {
            return {
                series: series || [],
                width: 640,
                height: 160,
                linePoints: '',
                areaPoints: '',
                firstLabel: '',
                lastLabel: '',

                init: function () {
                    var pts = this.series;
                    if (! pts.length) {
                        return;
                    }

                    var values = pts.map(function (p) { return p.cents; });
                    var max = Math.max.apply(null, values.concat([1]));
                    var min = Math.min.apply(null, values.concat([0]));
                    var range = (max - min) || 1;
                    var pad = 6;
                    var stepX = pts.length > 1 ? this.width / (pts.length - 1) : 0;

                    var coords = pts.map(function (p, i) {
                        var x = stepX * i;
                        var y = this.height - pad - (((p.cents - min) / range) * (this.height - pad * 2));
                        return x.toFixed(1) + ',' + y.toFixed(1);
                    }, this);

                    this.linePoints = coords.join(' ');
                    this.areaPoints = '0,' + this.height + ' ' + coords.join(' ') + ' ' + this.width + ',' + this.height;
                    this.firstLabel = pts[0].date;
                    this.lastLabel = pts[pts.length - 1].date;
                },
            };
        });

        /** Client-side preview of a picked file, before it is uploaded. */
        Alpine.data('imagePreview', function () {
            return {
                preview: null,

                onChange: function (event) {
                    var file = event.target.files && event.target.files[0];
                    if (! file) {
                        this.preview = null;
                        return;
                    }
                    var reader = new FileReader();
                    var self = this;
                    reader.onload = function (ev) { self.preview = ev.target.result; };
                    reader.readAsDataURL(file);
                },
            };
        });
    });

    function blankVariant() {
        return {
            id: null,
            option1_name: '', option1_value: '',
            option2_name: '', option2_value: '',
            option3_name: '', option3_value: '',
            sku: '', barcode: '',
            price: '', compare_at_price: '', cost: '',
            inventory_qty: 0, weight_grams: 0,
            track_inventory: true,
        };
    }

    /* ------------------------------------------------------------------ *
     * Slug auto-fill: any input tagged [data-slug-source="#target"] fills the
     * target slug field as the user types, until the target has been edited
     * by hand — used on the product and collection forms.
     * ------------------------------------------------------------------ */
    function slugify(value) {
        return value
            .toString()
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-slug-source]').forEach(function (source) {
            var target = document.querySelector(source.getAttribute('data-slug-source'));
            if (! target) {
                return;
            }

            var edited = target.value.trim().length > 0;
            target.addEventListener('input', function () { edited = true; });
            source.addEventListener('input', function () {
                if (! edited) {
                    target.value = slugify(source.value);
                }
            });
        });
    });
})();
