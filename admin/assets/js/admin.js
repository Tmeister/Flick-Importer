(function ( $ ) {
	"use strict";

	$(function () {

		var $el
		,	gallery
		,	title
		,	description
		,	count
		,	isImporting
        ,   termid;

		$('.status-holder').hide();

		$('.importer').click(function(event) {
			event.preventDefault();

			if( isImporting ){
				return;
			}

			$el = $(this);
			gallery = $el.data('fid');
			title = $el.data('name');
			description = $el.data('desc');
			isImporting = true;


			var data = {
				action: 'import_gallery',
				fid: gallery,
				title: title,
				description: description
			};

			$.post(ajax_object.ajax_url, data, function(response) {

				if( response.status == 'fail' ){
					alert( response.message );
					isImporting = false;
					return;
				}

				draw_set(response);

			}, 'json');
		});

        $('.reimporter').click(function(event){
            event.preventDefault();
            if( isImporting ){
                return;
            }

            $el = $(this);
            gallery = $el.data('fid');
            title = $el.data('name');
            description = $el.data('desc');
            termid = $el.data('termid');
            isImporting = true;

            var data = {
                action: 'reimport_gallery',
                fid: gallery,
                title: title,
                termid: termid,
                description: description
            };

            $.post(ajax_object.ajax_url, data, function(response) {

                if( response.status == 'fail' ){
                    alert( response.message );
                    isImporting = false;
                    return;
                }

                draw_set(response);

            }, 'json');
        });

		function draw_set(r) {
			$('.status-holder span.count').html(r.data.photoset.total);
			$('.status-holder').show();
			$('.realcount').html( '0 - ' + r.data.photoset.total );
			count = 0;
			import_photo(r);
		}

		function import_photo(r) {

			if( count >= r.data.photoset.total ){
				alert( 'The import finished correctly.' );
				isImporting = false;
				return;
			}

			var data = {
				action: 'import_photo',
				photoId: r.data.photoset.photo[count].id,
				photoTitle: r.data.photoset.photo[count].title,
				photosetID: r.photosetID
			};

			$.post(ajax_object.ajax_url, data, function(response) {
				if( response.status == 'fail' ){
					alert( response.message );
					isImporting = false;
					return;
				}
				$('.realcount').html( (count + 1) + ' - ' + r.data.photoset.total );
		 		console.log('Termino ' + response.pid);
				count++;
				import_photo(r);
			}, 'json');
		}

	});

}(jQuery));