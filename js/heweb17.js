jQuery(document).ready(function(){
    setTimeout(function(){
        jQuery('#pa_format').on('change', function() {
            showVariationAttributes();
        });
    }, 150);
    showVariationAttributes();
});



function showVariationAttributes() {
    if (jQuery('#pa_format')) {
        jQuery('.variation_attributes').hide();
        format = jQuery('#pa_format').val();
        jQuery('#attributes_' + format).show();
        if(format == 'ebook' && jQuery('#ebooklinks').length > 0) {
            jQuery('#ebooklinks').show();
            setTimeout(function(){
                jQuery('.variations_button').hide();
            }, 150);

        } else {
            jQuery('#ebooklinks').hide();
            setTimeout(function(){
                jQuery('.variations_button').show();
            }, 150);
        }
    }
}

