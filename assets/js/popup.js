define(['jquery-1.9'], function ($) {
    /**
     * Basic mixed JS popup object with pure CSS fallback.
     *
     * @param options
     */
    var popup = function(options) {
        this.containers = null;
        this.options = {};
        this.setOptions(options);
        this.init();
    };
    popup.prototype = {
        initial_options: {
            containerSelector: '.popup',
            attachSelector: '.programmes-page', // The branding/css style is lost if you attach this higher up
            bodySelector: '#orb-modules', // We use this rather than <html> because iOS doesn't bubble clicks up to the body
            fadeTime: 300,
            removeOnLinkClicked: true,
            useParentContainerWidth: false
        },
        classes: {
            popupHolder: 'popup__holder',
            visuallyHidden: 'visually-hidden',
            popupContent: 'popup__content',
            popupJS: 'popup__content--js',
            button: 'popup__button',
            close: 'popup__close',
            fallbackInput: 'popup__status'
        },
        setOptions : function (options) {
            this.options = $.extend({}, this.initial_options, options);
        },
        init: function() {
            this.containers = $(this.options.containerSelector);
            var _this = this;
            this.containers.find('.' + this.classes.popupHolder).addClass(this.classes.visuallyHidden);
            this.containers.find('.' + this.classes.button).click(function(e) {
                e.preventDefault();
                // Everything here is attached to the container element, not the JS object
                // in order to allow multiple popups per page.
                var container = $(this).parents(_this.options.containerSelector);
                if (container.data('popup')) {
                    _this.removePopup(container);
                } else {
                    _this.firePopup(container);
                }
            });
        },
        firePopup: function(container) {
            // We need to move this out of the container to prevent overflow:hidden murdering it
            var popup = container.find('.' + this.classes.popupContent).clone();
            popup.addClass(this.classes.popupJS);
            popup.hide().prependTo(this.options.attachSelector);
            // Let the container keep track of it's popups
            container.data('popup', popup);

            // Attach events (to a specific namespace for each popup)
            var id = this.getPopupId(container);
            var resizeTimer, _this = this;
            // Place popup correctly on window resize
            $(window).on('resize.popup#' + id, function(){
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    _this.positionPopup(container, _this.options.useParentContainerWidth);
                }, 300);
            });
            // Remove popup on background click
            $(this.options.bodySelector).on('click.popup#' + id, function(e) {
                var clickedElement = $(e.target);
                var clickedContainer = clickedElement.parents(_this.options.containerSelector);
                var clickedPopup = clickedElement.parents('.' + _this.classes.popupJS);
                if (clickedContainer.length < 1 &&  clickedPopup.length < 1) {
                    _this.removePopup(container);
                }

            });
            // Remove popup on close click
            popup.find('.' + this.classes.close).click(function(e){
                e.preventDefault();
                _this.removePopup(container);
            });
            // Remove on link click
            if (this.options.removeOnLinkClicked) {
                popup.find('a').click(function(e){
                    _this.removePopup(container);
                });
            }
            // Finally position and fade the bastard in
            this.positionPopup(container, this.options.useParentContainerWidth);
            popup.fadeIn(this.options.fadeTime);
        },
        removePopup: function(container) {
            var popup = container.data('popup');
            var id = this.getPopupId(container);
            $(window).off('resize.popup#' + id);
            $(this.options.bodySelector).off('click.popup#' + id);
            popup.fadeOut(this.options.fadeTime, function() {
                container.data('popup', false);
                popup.remove();
            });
        },
        positionPopup: function(container, useParentContainerWidth) {
            var popup = container.data('popup');
            var content = container.find('.' + this.classes.popupContent);
            var offset = content.offset();
            var width = container.width();

            if(useParentContainerWidth) {
                width = container.parent().width();
            }
            popup.css({
                'left' : offset.left + 'px',
                'top' : offset.top + 'px',
                'width' : width + 'px'
            });
        },
        getPopupId: function(container) {
            if (container && container.length > 0) {
                return container.find('.' + this.classes.fallbackInput).attr('id');
            }
            return '';

        }
    };
    return popup;
});
