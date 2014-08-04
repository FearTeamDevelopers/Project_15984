jQuery.noConflict();

jQuery(document).ready(function() {
    jQuery("#catvieworder").on("change", function() {
        var selectbox = jQuery(this).children('option:selected').val(),
        selectbox2 = jQuery("#catvieworderby").children('option:selected').val(),
                url = '/app/index/setproductorder/';
        jQuery.post(url, {catvieworder: selectbox, catvieworderby: selectbox2}, function(msg){
            window.location.replace(msg);
        });
        
    });
    
    jQuery("#catvieworderby").on("change", function() {
        var selectbox = jQuery(this).children('option:selected').val(),
        selectbox2 = jQuery("#catvieworder").children('option:selected').val(),
                url = '/app/index/setproductorder/';
        jQuery.post(url, {catvieworder: selectbox2, catvieworderby: selectbox}, function(msg){
            window.location.replace(msg);
        });
        
    });

});



    