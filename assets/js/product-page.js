jQuery(document).ready(function() {
    var MulberryProductPage = {
        element: jQuery('.cart'),
        productUpdateTimer: null,
        mulberryProductUpdateDelay: 1000,
        mulberryOverlayActive: false,
        warrantyHashElement: '#warranty_hash',
        warrantySkuElement: '#warranty_sku',

        /**
         * Register events
         */
        addProductListeners: function addProductListeners() {
            this.element.on('toggleWarranty', function(evt, params) {
                this.toggleWarranty(params.data, params.isSelected);
            }.bind(this));
        },

        initLibrary: function initLibrary() {
            if (window.mulberry) {
                this.registerProduct();
                this.addProductListeners();
                this.registerModal();

            } else {
                setTimeout(
                    function() {
                        this.initLibrary();
                    }.bind(this),
                    50
                );
            }
        },

        /**
         * Init Mulberry product
         */
        registerProduct: function registerProduct() {
            var self = this;

            window.mulberry.core.init({
                publicToken: window.mulberryConfigData.publicToken
            }).then(
                self.registerOffers()
            );
        },

        /**
         * Register inline & modal offers
         */
        registerOffers: function registerOffers() {
            var self = this;

            window.mulberry.core.getWarrantyOffer(window.mulberryProductData.product)
            .then(function(offers) {
                if (offers.length) {
                    var settings = window.mulberry.core.settings;

                    if (settings.has_modal) {
                        window.mulberry.modal.init({
                            offers,
                            settings,
                            onWarrantySelect: function(warranty) {
                                self.toggleWarranty(warranty, true);
                                window.mulberry.modal.close();

                                self.mulberryOverlayActive = true;
                                jQuery('.single_add_to_cart_button').click();
                                self.mulberryOverlayActive = false;

                                /**
                                 * Reset value for warranty element
                                 */
                                jQuery(this.warrantyHashElement).val('');
                            },
                            onWarrantyDecline: function() {
                                window.mulberry.modal.close();

                                self.mulberryOverlayActive = true;
                                jQuery('.single_add_to_cart_button').click();
                                self.mulberryOverlayActive = false;
                            }
                        });
                    }

                    if (settings.has_inline) {
                        self.hasInline = true;

                        window.mulberry.inline.init({
                            offers: offers,
                            settings: settings,
                            selector: '.mulberry-inline-container',
                            onWarrantyToggle: function(warranty) {
                                self.toggleWarranty(warranty.offer, warranty.isSelected);
                            }
                        });
                    }
                }
            });
        },

        /**
         * Update warranty product's hash
         *
         * @param data
         * @param isSelected
         */
        toggleWarranty: function toggleWarranty(data, isSelected) {
            var selectedWarrantyHash = '',
                warrantyHashElement = jQuery(this.warrantyHashElement),
                warrantySkuElement = jQuery(this.warrantySkuElement);

            if (data) {
                selectedWarrantyHash = isSelected && data ? data.warranty_hash : '';
            }

            warrantySkuElement.val(window.mulberryProductData.product.id);
            warrantyHashElement.val(selectedWarrantyHash);
        },

        /**
         * Override original Magento add to cart action
         */
        registerModal: function() {
            var self = this;

            if (window.mulberry) {
                this.element.on('submit', function(evt) {
                    console.log(window.mulberry.core.settings.has_modal);

                    if (window.mulberry.core.settings.has_modal) {
                        var e = null;

                        try {
                            if (
                                !self.mulberryOverlayActive &&
                                jQuery(self.warrantyHashElement).val() === ""
                            ) {
                                evt.preventDefault();
                                window.mulberry.modal.open();
                            }
                        } catch (e) {
                            console.error(e);
                        }

                        if (e) {
                            throw e;
                        }
                    }
                });
            }
        }
    };

    MulberryProductPage.initLibrary();
});
