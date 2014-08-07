jQuery.noConflict();

jQuery(document).ready(function() {
    
    jQuery(window).load(function() {
        jQuery("#loader, .loader").hide();
        
        jQuery.post('/app/system/showprofiler/', function(msg){
            jQuery('body').append(msg);
        });
    });
    
    jQuery('#loadmoreproducts').click(function(){
        jQuery.post('/app/category/categoryloadproducts/', function(msg){
            jQuery('.category').append(msg);
        });
    });
    
    jQuery("#catvieworder").on("change", function() {
        var selectbox = jQuery(this).children('option:selected').val(),
        selectbox2 = jQuery("#catvieworderby").children('option:selected').val(),
                url = '/app/category/setproductorder/';
        jQuery.post(url, {catvieworder: selectbox, catvieworderby: selectbox2}, function(msg){
            window.location.replace(msg);
        });
    });
    
    jQuery("#catvieworderby").on("change", function() {
        var selectbox = jQuery(this).children('option:selected').val(),
        selectbox2 = jQuery("#catvieworder").children('option:selected').val(),
                url = '/app/category/setproductorder/';
        jQuery.post(url, {catvieworder: selectbox2, catvieworderby: selectbox}, function(msg){
            window.location.replace(msg);
        });
    });
});