jQuery(document).ready(function($){
	$('#add-to-cart').click(function(){
		// Browser code
		var date = new Date();
		current_time = Math.round( date.getTime() / 1000 );

		var last_session = localStorage.getItem( 'jmenu_last_session' );

		if( last_session == null ){
			localStorage.setItem( 'jmenu_last_session', current_time );
		} else {
			if( current_time - last_session > 86400 ){
				localStorage.clear( 'jmenu_items' );
			}
		}

		// Verify if the item is already on the cart
		// True = Not added
		// False = Added
		var items = localStorage.getItem( 'jmenu_items' );
		var stop = 0;

		var dish_id = $(this).parent('form').find( '#dish_id' ).attr('value');
		var quantity = $(this).parent('form').find( '#quantity' ).attr('value');

		if( items == null ){
			localStorage.setItem( 'jmenu_items', dish_id );

			var data = {
				action: 'jmenu_add_to_cart',
				item: dish_id,
				quantity: quantity
			}
		} else {
			var items_array = items.split( ',' );

			for( var i in items_array ){
				if( items_array[i] == dish_id ){
					stop = 1;
				}
			}

			if( stop == 0 ){
				items = items+','+dish_id;
				localStorage.setItem( 'jmenu_items', items );

				var data = {
					action: 'jmenu_add_to_cart',
					item: dish_id,
					quantity: quantity
				}
			} else {
				var data = {
					action: 'jmenu_add_to_cart'
				}

			}
		}

		$.post( ajaxurl, data, function(response){
			alert( response );
		});

		return false;
	});

	$( '#clear-cart' ).click( function(){
		localStorage.clear( 'jmenu_items' );

		var data = {
			action: 'jmenu_clear_cart'
		}

		$.post( ajaxurl, data, function(response){
			alert( response );
		}); 

		return false;
	});
});