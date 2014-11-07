jQuery.noConflict();

jQuery(document).ready(function () {

    jQuery(window).on('scroll', function () {
        var scrollHeight = jQuery(document).height();
        var scrollPosition = jQuery(window).height() + jQuery(window).scrollTop();

        if ((scrollHeight - scrollPosition) / scrollHeight === 0) {
            jQuery.post('/app/category/categoryloadproducts/', function (msg) {
                jQuery('.category').append(msg);

                if (!jQuery('.scroll-top').is('visible') && scrollPosition > 5000) {
                    jQuery('.scroll-top').show('slow');
                }
            });
        }

        if (jQuery(window).height() < 925 && jQuery(window).scrollTop() > 250) {
            jQuery('nav').css('top', function () {
                return jQuery(window).scrollTop() - 180;
            });
        }

        if (jQuery(window).height() < 925 && jQuery(window).scrollTop() < 250) {
            jQuery('nav').css('top', '0px');
        }

        if (!jQuery('.scroll-top').is('visible') && scrollPosition > 5000) {
            jQuery('.scroll-top').show('slow');
        }
    });

    jQuery('.scroll-top').click(function () {
        jQuery(this).hide('slow');
    });
});