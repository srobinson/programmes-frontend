define(['jquery-1.9','rv-bootstrap', 'istats-1'], function ($, bootstrap, istats) {

    var Stream = function (container, options) {
        this.calc = {
            child_count : 0,
            child_width : 0,
            child_width_px : 0,
            gutter : 0,
            current_item : 0,
            items_in_window : 1,
            window_count : 1,
            current_window : 0,
            on_last : false
        };
        this.container = $(container);
        if (this.container.find('.' + this.classes.window).length > 0) {
            return;
        }

        this.setOptions(options);
        this.init();
    };

    Stream.prototype = {
        container : null,
        classes : {
            main    : 'stream',
            panel   : 'stream__panel',
            window  : 'stream__window',
            item    : 'stream__item',
            button  : 'stream__button'
        },
        options : {
            panel_class : '',
            snap : true,
            match_button_height : null, /* A selector to match for setting the height of the button */
            set_to_same : 'li', /* A selector to loop through for setting heights */
            window_width : 80, /* what percentage of overlap to display (will apply to both sides) */
            items_in_window : [
                { threshold : 0, number : 1 }
            ], /* how many items to fit in the window */
            button_width: '10%',
            next_button_text : '&gt;',
            previous_button_text : '&lt;',
            button_classes : 'stream__button--edges',
            button_wrap_classes : '',
            move_by_single : false,
            start_from: 0,
        },
        setOptions : function (options) {
            this.options = $.extend({}, this.options, options);
        },
        init : function () {
            this.setMarkup();
            this.addListeners();
        },
        getCurrentPosition : function () {
            return this.container.find('.' + this.classes.window).scrollLeft();
        },
        moveTo : function (left) {
            var _this = this;
            this.container.find('.' + this.classes.window).animate(
                { scrollLeft : left},
                function () {
                    _this.setButtons();
                    istats.log('click','programmes_global_carousel');
                }
            );
        },
        moveToNext : function () {
            var new_item = this.calc.current_item + 1;
            if (new_item >= this.calc.child_count) {
                new_item = (this.calc.child_count-1);
            }
            this.moveToItem(new_item);
        },
        moveToPrevious : function () {
            var new_item = this.calc.current_item - 1;
            if (new_item < 0) {
                new_item = 0;
            }
            this.moveToItem(new_item);
        },
        hasEqualWindows : function () {
            return !(this.calc.child_count % this.calc.items_in_window);
        },
        moveToNextWindow : function () {
            this.calc.on_last = false;
            var new_window = this.calc.current_window + 1;
            if (new_window >= this.calc.window_count) {
                new_window = (this.calc.window_count-1);
            }
            if ((new_window == (this.calc.window_count-1)) && !this.hasEqualWindows()) {
                this.moveToLastWindow();
            } else {
                this.moveToItem(this.firstItemInWindow(new_window));
            }
        },
        moveToLastWindow : function () {
            var remainder = (this.calc.child_count % this.calc.items_in_window),
                front_of_window = (this.calc.child_count - remainder) - 1,
                left = this.getPositionForItem(front_of_window);
            // override the window number
            this.calc.on_last = true;
            this.calc.current_item = front_of_window;
            this.calc.current_window = (this.calc.window_count-1);
            this.updateClasses();
            this.moveTo(left);
        },
        moveToPreviousWindow : function () {
            this.calc.on_last = false;
            var new_window = this.calc.current_window - 1;
            if (new_window < 0) {
                new_window = 0;
            }
            this.moveToItem(this.firstItemInWindow(new_window));
        },
        moveToWindow : function (number) {
            this.calc.on_last = false;
            if ((number == (this.calc.window_count-1)) && !this.hasEqualWindows()) {
                this.moveToLastWindow();
            } else {
                this.moveToItem(this.firstItemInWindow(number));
            }
        },
        firstItemInWindow : function (number) {
            return (this.calc.items_in_window * number);
        },
        getWindowForItem : function (number) {
            return Math.floor(number / this.calc.items_in_window);
        },
        calculateNearest : function() {
            var current = this.getCurrentPosition(),
                nearest = 0,
                smallest_difference = -1,
                difference = 0,
                i = 0;
            for (i = 0; i < this.calc.child_count; i += 1) {
                difference = Math.abs(this.getPositionForItem(i) - current);
                if (smallest_difference < 0 || difference <= smallest_difference) {
                    smallest_difference = difference;
                    nearest = i;
                }
            }
            return nearest;
        },
        getPositionForItem : function (number) {
            return (number * this.calc.child_width_px);
        },
        updateValues : function(number) {
            this.calc.current_item = number;
            this.calc.current_window = this.getWindowForItem(number);
            this.updateClasses();
        },
        snapAdjust : function(number) {
            var left = this.getPositionForItem(number);
            this.moveTo(left);
        },
        moveToItem  : function (number) {
            this.calc.on_last = false;
            var left = this.getPositionForItem(number);
            if (this.calc.current_item != number) {
                this.updateValues(number);
                this.moveTo(left);
            }
        },
        updateClasses : function() {
            var _this = this,
                items = this.container.find('.' + this.classes.item),
                prefix = 'stream__i_';
            items.each(function (index) {
                var item = $(this),
                    classes = [],
                    win_num = _this.getWindowForItem(index),
                    i, o_len;

                // remove all previous classes
                original_classes = item.attr('class').split(' ');
                o_len = original_classes.length;
                for (i=0;i<o_len;i++) {
                    if (original_classes[i].lastIndexOf(prefix, 0) !== 0) {
                        classes.push(original_classes[i]);
                    }
                }

                classes.push(prefix + index);
                classes.push(prefix + 'w_' + win_num);

                if (index == 0) {
                    classes.push(prefix + 'first');
                }
                if (index == (_this.calc.child_count-1)) {
                    classes.push(prefix + 'last');
                }

                if (index >= _this.calc.current_item && index < (_this.calc.current_item + _this.calc.items_in_window)) {
                    classes.push(prefix + 'visible');
                } else {
                    classes.push(prefix + 'hidden');
                }

                item.attr('class', classes.join(' '));
            });
        },
        enableButton : function (button) {
            button.removeClass('stream__button--removed').removeAttr('disabled');
            button.find('.gelicon').show();
        },
        disableButton : function (button) {
            button.attr('disabled', 'disabled');
            button.find('.gelicon').hide();
        },
        setButtons : function () {
            var prev = this.container.find('.' + this.classes.button + '--prev'),
                next = this.container.find('.' + this.classes.button + '--next'),
                match_height,
                _this = this;
            if (this.calc.child_count > this.calc.items_in_window) {
                this.enableButton(prev);
                this.enableButton(next);

                if (this.calc.current_item <= 0) {
                    //nothing off to the left
                    this.disableButton(prev);
                } else if ((this.calc.current_item + this.calc.items_in_window) >= this.calc.child_count) {
                    //nothing off to the right
                    this.disableButton(next);
                }
                if (this.options.match_button_height) {
                    match_height = this.container.find(this.options.match_button_height).height();
                    if (match_height > 0) {
                        prev.height(match_height);
                        next.height(match_height);
                    } else {
                        setTimeout(function() {
                            _this.setButtons();
                        }, 500)
                    }
                }
            } else {
                prev.addClass('stream__button--removed');
                next.addClass('stream__button--removed');
            }
        },
        setToSameHeight : function () {
            var items = this.container.find(this.options.set_to_same),
                tallest = 0;
            items.each(function () {
                var item = $(this);
                item.css('height', 'auto');
                height = item.height();
                if (height > tallest) {
                    tallest = height;
                }
            }).css('height', tallest + 'px');
        },
        setContainerHeight : function () {
            var win = this.container.find('.' + this.classes.window),
                container_height = win.height(),
                panel = this.container.find('.' + this.classes.panel),
                panel_height = panel.height(),
                difference = -(container_height - panel_height);
            win.css('margin-bottom', difference + 'px');
        },
        setMarkup : function () {
            var _this = this,
                win = $('<div class="' + this.classes.window + '"></div>'),
                panel = $('<div class="' + this.classes.panel + ' ' + this.options.panel_class + '"></div>'),
                list = this.container.children(),
                buttons = $('<div class="stream__buttons ' + this.options.button_wrap_classes + '"><div class="stream__buttons-wrap"><button class="' + this.classes.button + ' ' + this.classes.button + '--prev ' + this.options.button_classes + '">' + this.options.previous_button_text + '</button><button class="stream__button stream__button--next ' + this.options.button_classes + '">' + this.options.next_button_text + '</button></div></div>');

            this.container.addClass(this.classes.main);
            list.appendTo(panel);
            panel.appendTo(win);

            if (this.options.button_width) {
                buttons.find('button').css('width', this.options.button_width);
            }
            this.container.append(buttons);

            win.appendTo(this.container);


            this.calculateChildren();
            this.calculatePanel();
            this.setToSameHeight();
            this.setContainerHeight();

            if (this.options.start_from) {
                if (this.options.move_by_single) {
                    this.moveToItem(this.options.start_from);
                } else {
                    this.moveToWindow(this.getWindowForItem(this.options.start_from));
                }
            }
            this.updateClasses();


            setTimeout(function () {
                //_this.fetchImages();
                _this.setButtons();
            },100);
        },
        calculateChildren : function () {
            var children = this.container.find('.' + this.classes.item);
            this.calc.child_count = children.length;
            this.calc.child_width = (100 / this.calc.child_count);
            children.css('width', this.calc.child_width + '%');
        },
        getItemsInWindow : function (width) {
            var number_of_thresholds = this.options.items_in_window.length,
                items = null,
                i = 0;
            for (i = 0; i < number_of_thresholds; i += 1) {
                if (width >= this.options.items_in_window[i].threshold) {
                    items = this.options.items_in_window[i].number;
                }
            }
            return items;
        },
        calculatePanel : function () {
            var children = this.container.find('.' + this.classes.item),
                panel = this.container.find('.' + this.classes.panel),
                win = this.container.find('.' + this.classes.window),
                win_width = win[0].getBoundingClientRect().width,
                max_width = parseInt(children.css('max-width')),
                window_width = this.options.window_width,
                panel_width = 0;

            this.calc.items_in_window = this.getItemsInWindow(this.container.width());
            this.calc.window_count = Math.ceil(this.calc.child_count/this.calc.items_in_window);

            if (this.calc.child_count <= this.calc.items_in_window) {
                window_width = 100; // if no carousel, 100% width
            }

            if ((win_width * (window_width/100)) >= max_width) {
                // child is at its max width
                panel_width = (((max_width/win_width)*100)*(this.calc.child_count/this.calc.items_in_window));
            } else {
                panel_width = (window_width*(this.calc.child_count/this.calc.items_in_window));
            }
            panel.css('width', panel_width + '%');
            this.calc.child_width_px = children[0].getBoundingClientRect().width;

            this.calc.gutter_px = ((win_width - (this.calc.child_width_px*this.calc.items_in_window)) / 2);
            this.calc.gutter = (this.calc.gutter_px / win_width) * 100;
            panel.css('padding-left', this.calc.gutter + '%');
            panel.css('padding-right', this.calc.gutter + '%');
        },
        isLegacyAndroid : function() {
            /* Android < 2.3 can't scroll inside <div>. Must check for it */
            var ua = navigator.userAgent,
                androidversion;
            if (ua.indexOf('Android') >= 0) {
                androidversion = parseFloat(ua.slice(ua.indexOf('Android')+8));
                if (androidversion <= 2.3) {
                    return true;
                }
            }
            return false;
        },
        addListeners : function () {
            var _this = this,
                win = this.container.find('.' + this.classes.window),
                timer, scroll_position;
            this.container.find('.stream__button--prev').on('click', function () {
                if (_this.options.move_by_single) {
                    _this.moveToPrevious();
                } else {
                    _this.moveToPreviousWindow();
                }
            });
            this.container.find('.stream__button--next').on('click', function () {
                if (_this.options.move_by_single) {
                    _this.moveToNext();
                } else {
                    _this.moveToNextWindow();
                }
            });

            // Separate resize functions allows the context 'this' to be available in loadContents
            function onResizeComplete(){
                _this.calculatePanel();
                _this.setToSameHeight();
                _this.setButtons();
                _this.updateClasses();
                _this.snapAdjust(_this.calc.current_item);
            }

            function onScrollComplete(){
                var new_scroll_position = _this.container.find('.' + _this.classes.window).scrollLeft(),
                    number;
                if ((scroll_position - new_scroll_position) == 0) {
                    number = _this.calculateNearest();
                    if (_this.calc.current_item != number) {
                        _this.updateValues(number);
                        if (_this.options.snap) {
                            _this.snapAdjust(number);
                        }
                    }
                } else {
                    scroll_position = new_scroll_position;
                    setTimeout(onScrollComplete, 100)
                }
            }

            // Run whenever the window is resized
            $(window).on('resize', function () {
                clearTimeout(timer);
                timer = setTimeout(onResizeComplete, 300);
            });

            // Run whenever a scroll completes
            win.scroll(function (e) {
                if (e.which > 0 || e.type === "mousedown" || e.type === "mousewheel"){
                    win.stop();
                }
                clearTimeout(timer);
                timer = setTimeout(onScrollComplete, 200);
            });

            if (('ontouchstart' in window) || (window.DocumentTouch && document instanceof DocumentTouch) || ('ontouchstart' in document.documentElement)) {
                if (!this.isLegacyAndroid()) {
                    this.container.addClass('stream--touchable');
                }
                win.one('touchstart', function(event){
                    _this.container.addClass('touched');
                });
            } else if (window.navigator.pointerEnabled || window.navigator.msPointerEnabled) {
                this.container.addClass('stream--touchable');
                win.on('pointermove MSPointerMove', function(evt){
                    var type;
                    if (evt.originalEvent.pointerType) {
                        type = evt.originalEvent.pointerType;
                    } else if (evt.originalEvent.msPointerType) {
                        type = evt.originalEvent.msPointerType;
                    }
                    if (type == 'touch' || type == 2) {
                        /*  You may have used a mouse first, and then touch,
                            so on/off have to be done separately */
                        _this.container.addClass('touched');
                        win.off('pointermove MSPointerMove');
                    }
                });
            }
        }
    }

    return Stream;

});
