jQuery.noConflict();

jQuery(document).ready(function() {

    jQuery(window).load(function() {
        jQuery("#loader, .loader").hide();

        jQuery.post('/app/system/showprofiler/', function(msg) {
            jQuery('body').append(msg);
        });
    });

    jQuery(window).on("scroll", function() {
        var scrollHeight = jQuery(document).height();
        var scrollPosition = jQuery(window).height() + jQuery(window).scrollTop();
    
        if ((scrollHeight - scrollPosition) / scrollHeight === 0) {
            jQuery.post('/app/category/categoryloadproducts/', function(msg) {
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
    
    jQuery('.scroll-top').click(function() {
        jQuery(this).hide("slow");
    });

    jQuery("#catvieworder").on("change", function() {
        var selectbox = jQuery(this).children('option:selected').val(),
                selectbox2 = jQuery("#catvieworderby").children('option:selected').val(),
                url = '/app/category/setproductorder/';
        jQuery.post(url, {catvieworder: selectbox, catvieworderby: selectbox2}, function(msg) {
            window.location.replace(msg);
        });
    });

    jQuery("#catvieworderby").on("change", function() {
        var selectbox = jQuery(this).children('option:selected').val(),
                selectbox2 = jQuery("#catvieworder").children('option:selected').val(),
                url = '/app/category/setproductorder/';
        jQuery.post(url, {catvieworder: selectbox2, catvieworderby: selectbox}, function(msg) {
            window.location.replace(msg);
        });
    });
});