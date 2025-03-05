/**
 * Copyright since 2023 Floa Bank
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 * @author 202 ecommerce <tech@202-ecommerce.com>
 * @copyright 2023 Floa Bank
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */
define([
    'jquery',
    'loader',
    'mage/translate',
], function ($, $t) {
    'use strict';

    return function (config) {
        window.floapay = config.floapay;
        window.floapay.tries = 0;

        $(document).ready(function () {
            let widgetContainer = document.querySelector('.floa-widget');
            if (widgetContainer === null && floapay.offers.length && floapay.offers.length > 0) {
                setTimeout(window.initWidgetMagento, 150);
            }

            if (window.floapay.type === 'product') {
                $('#qty').on('change', floaLaunchAjaxRefresh);
                $('.product-info-price [data-price-amount]').on('DOMSubtreeModified', function() {
                    floaLaunchAjaxRefresh();
                });
            }
        });

        function floaLaunchAjaxRefresh()
        {
            let qty = $('#qty').val();
            let price = $('.product-info-price [data-price-amount] .price').text();
            var ajaxData = {
                ajax: true,
                price: Number(price.replace(',', '.').replace(/[^0-9.-]+/g,"")) * qty
            };
            launchFloaAjax(ajaxData);
        }
    
        function initWidgetMagento() {
            let widgetContainer = document.querySelector('.floa-widget');
            let touchStartY = 0;
            let touchEndY = 0;
    
            if (widgetContainer === null && floapay.offers.length && floapay.offers.length > 0) {
                if (typeof window.initFloaWidget === 'function') {
                    window.initFloaWidget();
                    widgetContainer = document.querySelector('.floa-widget');
                }
            }
    
            let floaWidget = $('.floa-widget');
            if (floaWidget.length !== 0) {
                widgetContainer.addEventListener('touchstart', e => {
                    touchStartY = e.changedTouches[0].screenY;
                });
    
                widgetContainer.addEventListener('touchend', e => {
                    touchEndY = e.changedTouches[0].screenY;
                    let widget = document.querySelector('.floa-widget .fl-popup-shown');
                    if (touchEndY - touchStartY > 0.33 * screen.height && widget) {
                        widget.classList.replace('fl-popup-shown', 'fl-popup-hidden');
                        document.body.style.removeProperty('overflow');
                        document.body.style.removeProperty('height');
                    }
                });
    
                window.initFloaWidgetWidth(floaWidget);
                $(window).on("resize", function () {
                    window.initFloaWidgetWidth(floaWidget);
                });
            }
            if (widgetContainer === null && window.floapay.tries < 5) {
                window.floapay.tries++;
                setTimeout(window.initWidgetMagento, 150);
            }
        }
        window.initWidgetMagento = initWidgetMagento;
        window.initFloaWidgetWidth = initFloaWidgetWidth;

        function initFloaWidgetWidth(floaWidget) {
            floaWidget.removeClass();
            if (floaWidget.width() <= 400) {
                floaWidget.addClass('floa-widget fl-jccenter fl-aicenter');
            } else {
                floaWidget.addClass('floa-widget fl-jcstart fl-aicenter');
            }
        }

        function launchFloaAjax(ajaxData) {
            $.ajax({
                url: floapay.url_product,
                type: 'POST',
                dataType: 'JSON',
                data: ajaxData,
                success(response) {
                    if (response.success === true && 'messages' in response) {
                        window.floapay.offers = response.messages.floapay.offers;
                        window.floapay.selectedOffer = response.messages.floapay.selectedOffer;
                        let widgetContainer = document.querySelector('.floa-widget');
                        if (widgetContainer && widgetContainer.outerHTML) {
                            widgetContainer.outerHTML = '<div data-floa-offers class="floa-widget"></div>';
                        }
                        setTimeout(function () {
                            window.initFloaWidget();
                            window.initWidgetMagento();
                        }, 150);
                    }
                },
                error(errorMessage) {
                    console.log(errorMessage);
                }
            });
        }
    }
});
