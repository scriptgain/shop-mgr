/**
 * ShopMGR storefront JS. Registers Alpine components used across the public
 * shop views: the product variant picker, quantity steppers, and the
 * checkout live-quote form. Loaded with `defer` after the Alpine CDN script,
 * so `alpine:init` always fires after this file has registered everything.
 */
document.addEventListener('alpine:init', () => {
    /**
     * Product variant picker. Keys into the server-built `variantMap`
     * (combo string, e.g. "Medium|Charcoal", or "default" for a product with
     * no options) to drive the displayed price, stock state, and the hidden
     * `variant_id` field — no pricing/stock logic is computed here, it only
     * looks up what the controller already prepared.
     */
    Alpine.data('variantPicker', (config) => ({
        axes: config.axes || [],
        variantMap: config.variantMap || {},
        selections: {},
        variantId: null,
        price: '',
        compareAt: null,
        sku: null,
        inStock: true,
        lowStock: false,
        qtyLeft: 0,

        init() {
            const initial = config.initial || [];
            this.axes.forEach((axis, i) => {
                if (initial[i] !== undefined) {
                    this.selections[axis.index] = initial[i];
                }
            });
            this.refresh();
        },

        key() {
            if (!this.axes.length) return 'default';
            return this.axes.map((axis) => this.selections[axis.index]).join('|');
        },

        current() {
            return this.variantMap[this.key()] || null;
        },

        select(axisIndex, value) {
            this.selections[axisIndex] = value;
            this.refresh();
        },

        refresh() {
            const variant = this.current();
            if (variant) {
                this.variantId = variant.id;
                this.price = variant.price;
                this.compareAt = variant.compare_at;
                this.sku = variant.sku;
                this.inStock = variant.in_stock;
                this.lowStock = variant.low_stock;
                this.qtyLeft = variant.qty;
            } else {
                this.variantId = null;
                this.price = '—';
                this.compareAt = null;
                this.sku = null;
                this.inStock = false;
                this.lowStock = false;
                this.qtyLeft = 0;
            }
        },

        isSelected(axisIndex, value) {
            return this.selections[axisIndex] === value;
        },

        /**
         * A pill is available when some variant exists with this value at
         * this axis position AND matches every other axis the shopper has
         * already picked (unselected axes are wildcards).
         */
        isAvailable(axisIndex, value) {
            const position = this.axes.findIndex((axis) => axis.index === axisIndex);
            if (position === -1) return true;

            return Object.keys(this.variantMap).some((key) => {
                const parts = key.split('|');
                if (parts[position] !== value) return false;

                return this.axes.every((axis, i) => {
                    if (i === position) return true;
                    const selected = this.selections[axis.index];
                    return selected === undefined || parts[i] === selected;
                });
            });
        },
    }));

    /** Small +/- quantity control shared by the product page and cart lines. */
    Alpine.data('quantityStepper', (initial = 1) => ({
        qty: initial,
        inc() {
            this.qty = Math.min(999, (parseInt(this.qty, 10) || 1) + 1);
        },
        dec() {
            this.qty = Math.max(1, (parseInt(this.qty, 10) || 1) - 1);
        },
    }));

    /**
     * Checkout live re-quote. Posts the shipping address + chosen rate to
     * shop.checkout.quote and swaps in whatever PricingService (via the
     * controller) computed — this file never adds up money itself.
     */
    Alpine.data('checkoutForm', (config) => ({
        address: config.address || { country: 'US', state: '', postcode: '' },
        totals: config.totals || {},
        discountError: config.discountError || null,
        selectedRateId: config.selectedRateId ?? null,
        needsShipping: !!config.needsShipping,
        billingSame: true,
        ratesLoaded: false,
        rates: [],
        quoting: false,

        quote() {
            this.quoting = true;

            fetch(config.quoteUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    shipping_address: this.address,
                    shipping_rate_id: this.selectedRateId,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    this.totals = data.totals;
                    this.rates = data.rates;
                    this.selectedRateId = data.selected_rate_id;
                    this.discountError = data.discount_error;
                    this.ratesLoaded = true;
                })
                .catch(() => {
                    // Network hiccup: leave the last known totals/rates in place
                    // rather than blanking a working quote.
                })
                .finally(() => {
                    this.quoting = false;
                });
        },
    }));
});
