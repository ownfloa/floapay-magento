define(['jquery'], function ($) {
    'use strict';

    return function (SwatchRenderer) {
        const originalUpdatePrice = SwatchRenderer.prototype._UpdatePrice;

        SwatchRenderer.prototype._UpdatePrice = function () {
            const newPrices = this._getNewPrices();

            $(document).trigger('floa:product:change', newPrices);

            originalUpdatePrice.apply(this, arguments);
        };

        return SwatchRenderer;
    };
});