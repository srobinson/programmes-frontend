define(['jquery-1.9', 'rv-bootstrap'], function ($, Bootstrap) {
    var Gallery = function(options) {
        this.setOptions(options);
        this.init();
    };
    Gallery.prototype = {
        options : {
            containerSelector: '#gallery',
            snapScrollTime: 200,
            clickScrollTime: 400,
            updateUrls: true,
            fadeTime: 400
        },
        classes: {
            slideShowContainer: 'gallery-slideshow',
            galleryWindow: 'gallery-slideshow__window',
            galleryListLink: 'gallery__listlink',
            tableau: 'gallery-slideshow__tableau',
            thumbContainer: 'gallery__thumblist',
            thumb: 'gallery__thumbnail',
            previousBtn: 'gallery__previousnext--prev',
            nextBtn: 'gallery__previousnext--next',
            previousNextBtn: 'gallery__previousnext',
            slide: 'gallery-slideshow__slide',
            currentSlide: 'gallery-slideshow__slide--current',
            nextSlide: 'gallery-slideshow__slide--next',
            previousSlide: 'gallery-slideshow__slide--previous',
            behindSlide: 'gallery-slideshow__slide--behind',
            fadedSlide: 'gallery-slideshow__slide--fadeout',
            galleryPosition: 'gallery__positiontext',
            galleryImageHolder: 'gallery-slideshow__imgholder',
            scrollable: 'gallery-slideshow__window--scrollable',
            active: 'gallery__thumbnail--active',
            touched: 'gallery-slideshow--touched',
            caption: 'gallery__caption',
            captionInner: 'island',
            initialised: 'gallery--initialised',
            slideShowInitialised: 'gallery-slideshow--initialised',
            listingPage: 'gallery--listview',
            imagePage: 'gallery--imgview'
        },
        attributes: {
            data_img_blank: 'data-img-blank',
            data_img_src: 'data-image-src',
            data_img_srcsets:'data-image-src-sets',
            data_img_sizes: 'data-image-sizes',
            data_img_title: 'data-gallery-title',
            data_img_synopsis: 'data-gallery-synopsis',
            data_img_position: 'data-gallery-position',
            data_page_url: 'data-gallery-url'

        },
        container: null, // Outer Gallery/Thumb container. Passed in in constructor.
        galleryWindow: null,
        initialised: false,
        previousSvg: 'null',
        nextSvg: 'null',
        pictureFill: null,
        position: 0,
        locked: false,
        scrollBarHeight: null,
        slidesData: [],
        /**
         * Extend options when initialising
         * @param {} options
         */
        setOptions : function (options) {
            this.options = $.extend({}, this.options, options);
        },
        /**
         * Initialise gallery data from page markup. Render gallery if main gallery element is visible
         * Allow for rendering gallery later at resize if main gallery element becomes visible (i.e. the thing
         * is hidden with a media query in CSS)
         */
        init : function () {
            // Get our container. Only do anything if it exists
            this.container = $(this.options.containerSelector);
            this.previousSvg = $(this.options.previousSvg);
            this.nextSvg = $(this.options.nextSvg);
            if (this.container.length < 1) {
                return;
            }
            this.galleryWindow = this.elementsByClass(this.classes.galleryWindow);
            this.pictureFill = Bootstrap.Picturefill;
            // Get data from our markup
            this.getGalleryData();

            this.initialiseGallery();
        },
        /**
         * Get an element from the gallery container by class name
         *
         * @param string className
         * @returns {jQuery}
         */
        elementsByClass: function(className) {
            return this.container.find('.' + className);
        },
        /**
         * Read gallery data from DOM. It's attached to the thumbnails.
         */
        getGalleryData: function() {
            var _this = this;
            this.elementsByClass(this.classes.thumb).each(function(){
                var position = parseInt(this.getAttribute(_this.attributes.data_img_position), 10);
                var slideData = {
                    position: position,
                    srcAttributes: _this.getImgSrcAttributes(this),
                    title: this.getAttribute(_this.attributes.data_img_title),
                    synopsis: this.getAttribute(_this.attributes.data_img_synopsis),
                    url: this.getAttribute(_this.attributes.data_page_url)
                };
                _this.slidesData[position] = slideData;
            });
            // Set current slide from thumbnail marked active
            var activeElement = this.elementsByClass(this.classes.active);
            if (activeElement.length) {
                this.position = parseInt(activeElement.attr(this.attributes.data_img_position), 10);
            }
        },
        /**
         * Get the name/value pairs of attributes on an element that relate to its image
         * source URL/Dimensions
         *
         * @param {DOM element} element
         * @returns {Array}
         */
        getImgSrcAttributes: function(element) {
            var attributes = element.attributes;
            var srcAttributes = {};
            for (var i = 0; i < attributes.length; i++) {
                var key = attributes[i].name;
                if (key == this.attributes.data_img_src
                    || key == this.attributes.data_img_srcsets
                    || key ==this.attributes.data_img_sizes
                ) {
                    srcAttributes[key] = attributes[i].value;
                }
            }
            return {
                'src': srcAttributes[this.attributes.data_img_src],
                'sizes': srcAttributes[this.attributes.data_img_sizes],
                'srcset': srcAttributes[this.attributes.data_img_srcsets],
            }
        },
        /**
         * Initialise the gallery
         */
        initialiseGallery: function() {
            if(this.initialised) {
                return;
            }
            if (this.container.hasClass(this.classes.listingPage)) {
                // Insert a gallery object into our DOM if on a listing page
                this.convertListingPageToImagePage();
            }
            // Only attach events and fire up a dynamic gallery if browser supports replaceState
            // Leave stuff as links otherwise
            this.initialised = true;
            this.container.addClass(this.classes.initialised);
            if (!this.browserSupportsHistory() || this.slidesData.length < 2) {
                return;
            }
            // Convert from non-slidey thing to slidey thing (this is a technical term)
            var slideShowContainer = this.elementsByClass(this.classes.slideShowContainer);
            slideShowContainer.addClass(this.classes.slideShowInitialised);
            var tableau = this.galleryWindow.find('.' + this.classes.tableau);
            tableau.prepend($('<div />', {'class': this.classes.slide + ' ' + this.classes.previousSlide}));
            tableau.append($('<div />', {'class': this.classes.slide + ' ' + this.classes.nextSlide}));
            // make window scrollable
            this.galleryWindow.addClass(this.classes.scrollable);
            // Move previous/next links from slide to container
            this.elementsByClass(this.classes.nextBtn).detach().prependTo(slideShowContainer);
            this.elementsByClass(this.classes.previousBtn).detach().prependTo(slideShowContainer);

            this.scrollToCentre();
            this.fixScrollBars();
            this.loadPreviousNext();
            this.fixArrowHeights();
            this.attachEvents();
        },
        /**
         * Make DOM changes to convert the gallery listing page into an image view page
         * Slightly nasty, but this is what the designs call for
         */
        convertListingPageToImagePage: function() {
            var tableau = this.elementsByClass(this.classes.tableau);
            // Add the first slide in JS on the gallery page. It's not in the HTML
            var slide = this.createSlideDOM(this.getCurrentSlideData());
            slide.addClass(this.classes.currentSlide);
            tableau.append(slide);
            var previous = $('<a />', {'class': this.classes.previousNextBtn + ' ' + this.classes.previousBtn})
            previous.append(this.previousSvg[0]);
            var next = $('<a />', {'class': this.classes.previousNextBtn + ' ' + this.classes.nextBtn});
            next.append(this.nextSvg[0]);
            this.elementsByClass(this.classes.slideShowContainer).prepend(next).prepend(previous);
            // Change container class
            this.container.removeClass(this.classes.listingPage).addClass(this.classes.imagePage);
            // Load images. Update URL etc.
            this.loadImages();
            this.updatePageFurniture();
        },
        /**
         * Add event handlers here
         */
        attachEvents: function() {
            var _this = this;
            // Previous/Next click
            this.elementsByClass(this.classes.slideShowContainer).on('click', 'a', function(event) {
                event.preventDefault();
                var $this = $(this);
                if ($this.hasClass(_this.classes.previousBtn)) {
                    _this.swipeDirection('left', _this.options.clickScrollTime);
                } else if ($this.hasClass(_this.classes.nextBtn)) {
                    _this.swipeDirection('right', _this.options.clickScrollTime);
                }
            });
            // Thumbnail Click
            this.elementsByClass(this.classes.thumbContainer).on('click', '.' + this.classes.thumb, function(event) {
                event.preventDefault();
                var position = this.getAttribute(_this.attributes.data_img_position);
                var slideData = _this.getSlideDataAt(position);
                if (slideData) {
                    _this.fadeToSlide(slideData);
                    _this.scrollToImage();
                }
            });
            // Make sure that the window doesn't scroll on resize
            var resizeTimer;
            $(window).on('resize.galleryScrollFix', function(){
                _this.scrollToCentre();
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    _this.fixArrowHeights();
                }, 300);
            });

            // Keyboard support
            $(document).keydown(function(e){
                if (e.keyCode == 37) {
                    // left arrow
                    _this.swipeDirection('left', _this.options.clickScrollTime);
                    return false;
                }
                if (e.keyCode == 39) {
                    // right arrow
                    _this.swipeDirection('right', _this.options.clickScrollTime);
                    return false;
                }
            });

            // Prevent previous/next arrows on touch devices
            var slideShowContainer = this.elementsByClass(this.classes.slideShowContainer);
            slideShowContainer.on('touchstart.arrowFix', function(e){
                slideShowContainer.addClass(_this.classes.touched);
                slideShowContainer.off('touchstart.arrowFix');
            });

            // Touch events and scrolling
            var scrollTimer, touching = false, scrolling = false;

            this.galleryWindow.on({
                'touchstart' : function(e) {
                    touching = true;
                },
                'touchend' : function(e) {
                    touching = false;
                    if (!scrolling) {
                        _this.snapToScrollPositionAndLoad();
                    }
                },
                'touchcancel': function(e) {
                    touching = false;
                },
                //MS Specific touch events (DAMN YOU MS)
                'pointerdown MSPointerDown': function(e){
                    if (_this.isMsTouch(e)) {
                        touching = true;
                    }
                },
                'pointerup MSPointerUp': function(e){
                    if (_this.isMsTouch(e)) {
                        touching = false;
                        if (!scrolling) {
                            _this.snapToScrollPositionAndLoad();
                        }
                    }
                },
                'pointerout MSPointerOut': function(e){
                    if (_this.isMsTouch(e)) {
                        touching = false;
                    }
                },
                'scroll': function (e) {
                    scrolling = true;
                    clearTimeout(scrollTimer);
                    scrollTimer = setTimeout(function() {
                        scrolling = false;
                        // Detect hitting left/right edge (not all UAs fire touchend properly)
                        var scrollPos = _this.galleryWindow.scrollLeft();
                        var scrollMax = (_this.galleryWindow.width() * 2) - 20;
                        var scrollMin = 20;
                        var scrolledToEnd = (scrollPos <= scrollMin || scrollPos >= scrollMax);
                        if (!touching || scrolledToEnd) {
                            _this.snapToScrollPositionAndLoad();
                        }
                    }, 125);
                }
            });
        },
        isMsTouch : function(jqueryEvent) {
            var type;
            if (jqueryEvent.originalEvent.pointerType) {
                type = jqueryEvent.originalEvent.pointerType;
            } else if (jqueryEvent.originalEvent.msPointerType) {
                type = jqueryEvent.originalEvent.msPointerType;
            }
            if (type == 'touch' || type == 2) {
                return true;
            }
            return false;
        },
        /**
         * Snap to the nearest slide. If that isn't the current slide,
         * update the slide display to the new one
         */
        snapToScrollPositionAndLoad: function() {
            var slideWidth = this.galleryWindow.width();
            var scrollPosition = this.galleryWindow.scrollLeft();
            var scrollDistance = scrollPosition - slideWidth;
            var direction = 'right';
            if (scrollDistance < 0) {
                direction = 'left';
                scrollDistance *= -1;
            }
            if (scrollDistance > (slideWidth / 3)) {
                // adjust scroll time depending on how far to scroll
                var pixelsFromEdge = (slideWidth - scrollDistance);
                var time = parseInt((this.options.snapScrollTime * (pixelsFromEdge / slideWidth)), 10);
                time = time < 0 ? time * -1 : time;
                if (time < 5) {
                    time = 5;
                }
                this.swipeDirection(direction, time);
            } else if(scrollDistance > 0) {
                this.swipeCentre(this.options.snapScrollTime);
            }
        },
        /**
         * Animate left or right from the current slide to the previous/next.
         * Update the page furniture and load in the new previous/next slides
         *
         * @param left|right direction
         * @param int duration
         */
        swipeDirection: function(direction, duration) {
            if (this.isLocked()) {
                return;
            }
            this.lock();

            var tableau = this.elementsByClass(this.classes.tableau);
            var currentSlideContainer = this.elementsByClass(this.classes.currentSlide);

            var newSlideContainer, left, slideData;
            if (direction == 'left') {
                newSlideContainer = this.elementsByClass(this.classes.previousSlide);
                left = 0;
                slideData = this.getPreviousSlideData();
            } else {
                newSlideContainer = this.elementsByClass(this.classes.nextSlide);
                left = 2 * currentSlideContainer.width();
                slideData = this.getNextSlideData();
            }

            var _this = this;
            this.galleryWindow.animate(
                { scrollLeft : left},
                duration,
                'linear',
                function () {
                    currentSlideContainer.remove();
                    var replacementDiv = $('<div />', {'class': _this.classes.slide});
                    if (direction == 'left') {
                        replacementDiv.addClass(_this.classes.previousSlide);
                        tableau.prepend(replacementDiv);
                    } else {
                        replacementDiv.addClass(_this.classes.nextSlide);
                        tableau.append(replacementDiv);
                    }
                    newSlideContainer.removeClass(_this.classes.nextSlide).removeClass(_this.classes.previousSlide);
                    newSlideContainer.addClass(_this.classes.currentSlide);
                    _this.scrollToCentre();
                    _this.setCurrentSlide(slideData);
                    _this.loadPreviousNext();
                    _this.updatePageFurniture();
                    _this.unlock();
                }
            );
        },
        /**
         * Animate back to the centre of the slide display
         * @param duration
         */
        swipeCentre: function(duration) {
            if (this.isLocked()) {
                return;
            }
            this.lock();
            var left = this.elementsByClass(this.classes.currentSlide).width();
            var unlock = $.proxy(this.unlock, this);
            this.galleryWindow.animate(
                { scrollLeft : left},
                duration,
                'linear',
                unlock
            );
        },
        /**
         * Fade animation to an arbitrarily chosen slide
         * @param {*} slideData
         */
        fadeToSlide: function(slideData) {
            if (this.isLocked()) {
                return;
            }
            this.lock();
            var _this = this;
            var tableau = this.elementsByClass(this.classes.tableau);
            var currentSlideContainer = tableau.find('.' + this.classes.currentSlide);
            var newSlideContainer = this.createSlideDOM(slideData);
            newSlideContainer.addClass(this.classes.behindSlide);
            newSlideContainer.insertAfter(currentSlideContainer);
            this.loadImages();
            var time = this.options.fadeTime / 1000;
            currentSlideContainer.css({transition: 'opacity ' + time + 's linear'});
            var fired = false;
            currentSlideContainer.on("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd", function(){
                if (fired) {
                    return;
                }
                fired = true;
                currentSlideContainer.remove();
                newSlideContainer.addClass(_this.classes.currentSlide);
                newSlideContainer.removeClass(_this.classes.behindSlide);
                _this.setCurrentSlide(slideData);
                _this.loadPreviousNext();
                _this.updatePageFurniture();
                _this.unlock();
            });
            // Allow the replacement slide time to load into the DOM
            setTimeout(function() {
                currentSlideContainer.addClass(_this.classes.fadedSlide);
            }, 10);
        },
        /**
         * Immediately scroll the tableau to the centre image
         */
        scrollToCentre: function() {
            this.galleryWindow.scrollLeft(this.galleryWindow.width());
        },
        /**
         * Scroll the page to the top of the image if required
         */
        scrollToImage: function() {
            var imageTop = this.galleryWindow.offset().top;
            var scrollPos = $(window).scrollTop();
            if (scrollPos > imageTop) {
                var containerTop = this.container.offset().top;
                var topDivHeight = imageTop - containerTop;
                var heightWithTopDiv = this.galleryWindow.height() + topDivHeight;
                if ($(window).height() > heightWithTopDiv) {
                    // If the users window is big enough, scroll to the top of the container
                    $(window).scrollTop(containerTop);
                } else {
                    // Otherwise scroll to the top of the image (16:9 phones with the image fullscreen)
                    $(window).scrollTop(imageTop);
                }
            }
        },
        /**
         * Lock the gallery so that users can't scroll about while we're animating things
         */
        lock: function() {
            if (!this.locked) {
                this.galleryWindow.removeClass(this.classes.scrollable);
                this.galleryWindow.css({marginBottom: 0});
                this.locked = true;
            }
        },
        /**
         * Unlock gallery locked this this.lock()
         */
        unlock: function() {
            if (this.locked) {
                this.galleryWindow.addClass(this.classes.scrollable);
                this.fixScrollBars();
                this.locked = false;
            }
        },
        /**
         * Check if gallery is locked
         * @returns {boolean}
         */
        isLocked: function() {
            return this.locked;
        },
        /**
         * Create a jQuery element object from data for a single slide
         * @param slideData
         * @returns {jQuery}
         */
        createSlideDOM: function(slideData) {
            var container = $('<div />', {'class': this.classes.slide});
            /**
             * Why create a <div> and load it using the responsive image processor? Am I a bit nuts?
             * Well, clearly, but that's not why.
             * This should create and load image at the correct size without triggering a load of it at the wrong size.
             * Which tends to happen when just creating an <img>
             */
            var rspImg = $('<img />', {'class': 'image'});
            rspImg.attr(slideData.srcAttributes);
            rspImg.attr('alt', slideData.title);
            var imgContainer = $('<div />', {'class': this.classes.galleryImageHolder});
            imgContainer.append(rspImg);
            container.append(imgContainer);
            // Caption
            var caption = $('<div />', {'class': this.classes.caption + ' br-box-subtle'});
            var title = $('<h2>').text(slideData.title);
            var synopsis = $('<p>').text(slideData.synopsis);
            var captionInner = $('<div />', {
                'class' : this.classes.captionInner
            }).append(title).append(synopsis);
            caption.append(captionInner);
            container.append(caption);
            return container;
        },
        /**
         * Load the previous and next images into the DOM to the left/right of the current one
         */
        loadPreviousNext: function() {
            var previous = this.createSlideDOM(this.getPreviousSlideData());
            previous.addClass(this.classes.previousSlide);
            this.elementsByClass(this.classes.previousSlide).replaceWith(previous);

            var next = this.createSlideDOM(this.getNextSlideData());
            next.addClass(this.classes.nextSlide);
            this.elementsByClass(this.classes.nextSlide).replaceWith(next);
            this.loadImages();
        },
        /**
         * Set current position from slide Data
         * @param slideData
         */
        setCurrentSlide: function(slideData) {
            this.position = slideData.position;
        },
        /**
         * Update the URL and all the bits and pieces of the page
         * outside of the gallery itself to reflect the current slide
         * (indicated by this.position)
         */
        updatePageFurniture: function() {
            var slideData = this.getCurrentSlideData();
            var that = this;
            // Defer this so that we return quicker to make the image changes feel more responsive.
            setTimeout(function() {
                // Update page URLs
                that.updateUrl(slideData.title, slideData.url);
                // Update 7/10 text
                that.elementsByClass(that.classes.galleryPosition).text((slideData.position + 1) + '/' + that.slidesData.length);
            }, 50);
            // Update previous/next links
            var previousSlideData = this.getPreviousSlideData();
            this.elementsByClass(this.classes.previousBtn).attr('href', previousSlideData.url);
            var nextSlideData = this.getNextSlideData();
            this.elementsByClass(this.classes.nextBtn).attr('href', nextSlideData.url);
            // Update active thumbnail
            var oldActive = this.elementsByClass(this.classes.active);
            oldActive.attr('href', oldActive.attr(this.attributes.data_page_url));
            oldActive.removeClass(this.classes.active);
            var activeThumb = this.elementsByClass(this.classes.thumb + '[' + this.attributes.data_img_position + '="' + slideData.position + '"]');
            activeThumb.addClass(this.classes.active);
            activeThumb.removeAttr('href');
            this.fixArrowHeights();
        },
        /**
         * Keep arrows centered on the image
         */
        fixArrowHeights: function() {
            var top = this.elementsByClass(this.classes.galleryImageHolder).outerHeight() / 2;
            this.elementsByClass(this.classes.previousBtn).css({top: top});
            this.elementsByClass(this.classes.nextBtn).css({top: top});
        },
        /**
         * Get data object for the current slide
         * @returns {*}
         */
        getCurrentSlideData: function() {
            return this.getSlideDataAt(this.position);
        },
        /**
         * Get data object for the previous slide
         * @returns {*}
         */
        getPreviousSlideData: function() {
            if (this.position < 1) {
                var len = this.slidesData.length;
                return this.getSlideDataAt(len -1);
            }
            return this.getSlideDataAt(this.position - 1);
        },
        /**
         * Get data object for the next slide
         * @returns {*}
         */
        getNextSlideData: function() {
            var len = this.slidesData.length;
            if (this.position >= (len - 1)) {
                return this.getSlideDataAt(0);
            }
            return this.getSlideDataAt(this.position + 1);
        },
        /**
         * Get data object for the specified slide if it exists
         * @returns {*}|null
         */
        getSlideDataAt: function(position) {
            if (position >= 0 && this.slidesData[position] != undefined) {
                return this.slidesData[position];
            }
            return null;
        },
        /**
         * Fire standard responsive image loading on the gallery container. Don't reinvent the wheel...
         */
        loadImages: function() {
            if (this.pictureFill) {
                this.pictureFill(this.galleryWindow.get(0));
            }
        },
        /**
         * Check whether browser supports pushState/replaceState
         * @returns {boolean}
         */
        browserSupportsHistory: function() {
            return !!(window.history && history.pushState);
        },
        /**
         * Push a URL into the location bar without updating history
         * @param string title
         * @param string url
         */
        updateUrl: function(title, url) {
            if (this.browserSupportsHistory() && this.options.updateUrls) {
                window.history.replaceState( {} , title, url );
            }
        },
        /**
         * Convert relative to absolute url
         *
         * @param string url
         * @returns string
         */
        urlToAbsolute: function(url) {
            // Just Check if we have a sensible protocol. All URLs should really be /programmes/x anyway
            // Feel free to implement proper rfc3986 compliant URL parsing if you have the time ;-)
            if (url.indexOf('http://') === 0 || url.indexOf('https://') === 0 || url.indexOf('//') === 0 ) {
                return url;
            }
            url = url.replace(/^\//, '');
            return 'http://www.bbc.co.uk/' + url;
        },
        /**
         * Eww. I know. We kind of need this here though.
         * @returns {number}
         */
        getScrollbarSize: function() {
            var css = {
                margin: 0,
                padding: 0,
                border: 'none',
                width: '100px',
                height: '100px'
            };
            var container = $('<div />').css(css);
            var item = $('<div />').css(css);
            container.css({
                top: '-1000px',
                overflowX: 'scroll',
                position: 'absolute'
            });
            container.addClass('gallery__hidescrollbars');
            container.append(item);
            $('body').append(container);
            container.scrollTop(1000);
            var height = (container.offset().top - item.offset().top) || 0;
            container.remove();
            return height;
        },
        /**
         * Hide scrollbars by popping them behind the container
         * div with a negative margin.
         */
        fixScrollBars: function() {
            if (this.scrollBarHeight === null) {
                this.scrollBarHeight = this.getScrollbarSize();
            }
            if (this.scrollBarHeight) {
                var height = this.scrollBarHeight * -1;
                this.galleryWindow.css({marginBottom: + height + 'px'});
            }
        }
    };
    return Gallery;
});
