/*
 * multi-image.js - Add support for multiple images to the post form
 *
 * Copyright (c) 2014 Fredrick Brennan <admin@8chan.co>
 *
 * Usage:
 *   $config['max_images'] = 3;
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/multi-image.js';
 */

function multi_image() {
	if (max_images != 1){
		$('input[type=file]').after('<a href="#" class="add_image">+</a>');
    }
    $(document).on('click', 'a.add_image', function(e) {
        e.preventDefault();

        var images_len = $('form:not([id="quick-reply"]) [type=file]').length;
        
        if (!(images_len >= max_images)) {
            $('form[name="post"]:first #upload [type=file]:last').after('<br class="file_separator"/><input type="file" name="file'+(images_len+1)+'" id="upload_file'+(images_len+1)+'">');
			if ($("#quick-reply").length > 0){
				$('#quick-reply #upload [type=file]:last').after('<br class="file_separator"/><input type="file" name="file'+(images_len+1)+'" id="upload_file'+(images_len+1)+'">');
			}
			if ($('form:not([id="quick-reply"]) [type=file]').length == max_images){
				$(".add_image").remove();
			}
            if (typeof setup_form !== 'undefined') setup_form($('form[name="post"]'));
        } 
    })
}

if (active_page == 'thread' || active_page == 'index' && max_images > 1) {
	$(document).ready(multi_image);
}
