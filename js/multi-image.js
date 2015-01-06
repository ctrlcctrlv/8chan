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
    $('input[type=file]').after('<a href="#" class="add_image">+</a>');
    $(document).on('click', 'a.add_image', function(e) {
        e.preventDefault();
        if (!($('form:not(#quick-reply) [type=file]').length >= max_images)) {
            $('a.add_image').before('<br><input type="file" name="files[]">');
            if (typeof setup_form !== 'undefined') setup_form($('form[name="post"]'));
        }
    })
}

if (active_page == 'thread' || active_page == 'index' && max_images > 1) {
	$(document).ready(multi_image);
}
