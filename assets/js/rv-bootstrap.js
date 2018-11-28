define(['jquery', 'lazyload', 'istats-tracking', 'lazysizes'],function($, Lazyload, IstatsTracking, lazysizes){
    var picturefill = null;
    $(function() {
        // Lazy sizes (as of v4.0.2) breaks in IE11 without this hack
        window.lazySizes = lazysizes;
        // Load responsive image polyfill if needed
        var image = document.createElement( "img" );
        if (!("srcset" in image) || !("sizes" in image) || !(window.HTMLPictureElement)) {
            require(['picturefill'], function (picturefilllib) {
                picturefill = picturefilllib;
            })
        }

        var responsiveLazyload = new Lazyload();
        responsiveLazyload.init();

        var tracking = new IstatsTracking();
        tracking.init();
        $('body').on('lazyloadComplete', function(e, context) {
            if (context && context.content) {
                tracking.trackLinks(context.content);
            }
        });

        var fontFamily = "font-family: Helvetica, Arial, sans-serif",
            fontStyling = fontFamily + "; font-size: 32px; line-height: 1.5; color: #fff",
            beebBlock = fontStyling + "; background-color: #000; padding: 4px 14px",
            dAndEBlock = fontStyling + "; background-color: #c8c8c8; color: #000; padding: 4px 10px",
            l = fontFamily + "; font-size: 14px; line-height: 1.15rem",
            c = l + "; text-decoration: underline",
            blockEnding = "";
        console.log("\n%cB%c %cB%c %cC%c %cDesign + Engineering%c\n\n%cIf you're interested in how this website was built, visit https://github.com/bbc/programmes-frontend\n" +
            "If you're looking for a BBC role in Technology, Design and Engineering; visit https://careerssearch.bbc.co.uk/jobs/category/57%c\n%c\n", beebBlock, blockEnding, beebBlock, blockEnding, beebBlock, blockEnding, dAndEBlock, blockEnding, l, c, l);
    });

    return {
        $: $,
        Lazyload: Lazyload,
        Picturefill: picturefill
    }
});
