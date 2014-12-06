if (active_page == 'catalog') $(function(){

	if (localStorage.catalog !== undefined) {
		var catalog = JSON.parse(localStorage.catalog);
	} else {
		var catalog = {};
		localStorage.catalog = JSON.stringify(catalog);
	}

	$("#sort_by").change(function(){
		var value = this.value;
		$('#Grid').mixItUp('sort', value);
		catalog.sort_by = value;
		localStorage.catalog = JSON.stringify(catalog);
		$('.threads .thread .replies').perfectScrollbar('update');
	});

	$("#image_size").change(function(){
		var value = this.value, old;
		$(".grid-li").removeClass("grid-size-vsmall");
		$(".grid-li").removeClass("grid-size-small");
		$(".grid-li").removeClass("grid-size-large");
		$(".grid-li").addClass("grid-size-"+value);
		catalog.image_size = value;
		localStorage.catalog = JSON.stringify(catalog);
		$('.threads .thread .replies').perfectScrollbar('update');
	});

	$('#Grid').mixItUp({
		animation: {
			enable: false
		},
	});

	if (catalog.sort_by !== undefined) {
		$('#sort_by').val(catalog.sort_by).trigger('change');
	}
	if (catalog.image_size !== undefined) {
		$('#image_size').val(catalog.image_size).trigger('change');
	}
	
	$('.threads .thread .replies').perfectScrollbar()
});
