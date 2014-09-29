jQuery.noConflict();

jQuery(document).ready(function () {

    jQuery(window).load(function () {
        jQuery("#loader, .loader").hide();

        jQuery.post('/app/system/showprofiler/', function (msg) {
            jQuery('body').append(msg);
        });
    });

    document.oncontextmenu = function (e) {
        e = e || window.event;
        if (/^img$/i.test((e.target || e.srcElement).nodeName))
            return false;
    };

    jQuery(window).on("scroll", function () {
        var scrollHeight = jQuery(document).height();
        var scrollPosition = jQuery(window).height() + jQuery(window).scrollTop();

        if ((scrollHeight - scrollPosition) / scrollHeight === 0) {
            jQuery.post('/app/category/categoryloadproducts/', function (msg) {
                jQuery('.category').append(msg);

                if (!jQuery('.scroll-top').is('visible') && scrollPosition > 5000) {
                    jQuery('.scroll-top').show("slow");
                }
            });
        }

        if (!jQuery('.scroll-top').is('visible') && scrollPosition > 5000) {
            jQuery('.scroll-top').show("slow");
        }
    });

    jQuery('.scroll-top').click(function () {
        jQuery(this).hide("slow");
    });

    jQuery("#catvieworder").on("change", function () {
        var selectbox = jQuery(this).children('option:selected').val(),
                selectbox2 = jQuery("#catvieworderby").children('option:selected').val(),
                url = '/app/category/setproductorder/';
        jQuery.post(url, {catvieworder: selectbox, catvieworderby: selectbox2}, function (msg) {
            window.location.replace(msg);
        });
    });

    jQuery("#catvieworderby").on("change", function () {
        var selectbox = jQuery(this).children('option:selected').val(),
                selectbox2 = jQuery("#catvieworder").children('option:selected').val(),
                url = '/app/category/setproductorder/';
        jQuery.post(url, {catvieworder: selectbox2, catvieworderby: selectbox}, function (msg) {
            window.location.replace(msg);
        });
    });
});

jQuery(function () {
    var pixelSource = '/public/images/transparent.png';
    var useOnAllImages = true;
    // Preload the pixel
    var preload = new Image();
    preload.src = pixelSource;

    jQuery('img').on('mouseenter touchstart', function (e) {
        // Only execute if this is not an overlay or skipped
        var img = jQuery(this);
        if (img.hasClass('protectionOverlay'))
            return;
        if (!useOnAllImages && !img.hasClass('protectMe'))
            return;
        // Get the real image's position, add an overlay
        var pos = img.offset();

        var overlay = jQuery('<img class="protectionOverlay" src="' + pixelSource + '" width="' + img.width() + '" height="' + img.height() + '" />').css({position: 'absolute', zIndex: 9999999, left: 0, top: 0})
                .appendTo(jQuery(this).parents('span, div.productPhoto a'))
                .bind('mouseleave', function () {
                    setTimeout(function () {
                        overlay.remove();
                    }, 0, jQuery(this));
                });

        if ('ontouchstart' in window)
            jQuery(document).on('touchend', function () {
                setTimeout(function () {
                    overlay.remove();
                }, 0, overlay);
            });
    });
});