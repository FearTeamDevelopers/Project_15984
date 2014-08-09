jQuery.noConflict();

jQuery(document).ready(function() {

    jQuery('.profiler-show-query').click(function() {
        jQuery('#profiler-query').toggle();
    });
    
    jQuery('.profiler-show-globalvar').click(function() {
        jQuery('#profiler-globalvar').toggle();
    });

    jQuery('#profiler-query tr:first').click(function() {
        jQuery('#profiler-query').toggle();
    });

    jQuery('#profiler-query tr td.backtrace').click(function() {
        var height = jQuery(this).css('height');
        var heightNum = height.replace('px', '');

        if (heightNum >= 250) {
            jQuery(this).parent('tr').css('height', '40px');
        } else {
            jQuery(this).parent('tr').css('height', '300px');
        }

        jQuery(this).children('div').toggle();
    });
});