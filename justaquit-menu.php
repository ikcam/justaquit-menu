<?php
/*
Plugin Name: JustAquit Menu
Plugin URI: http://justaquit.com
Description: This plugins to manage a menu from a restaurant.
Version: 1.0
Author: Irving Kcam
Author URI: http://ikcam.com
License: GPL2
*/

Class JA_Menu {
	public function __construct(){
		add_action( 'init', array( $this, 'post_type_support' ) );
		add_action( 'init', array( $this, 'post_type_register' ) );
		add_action( 'init', array( $this, 'taxonomy_register') );
		add_action( 'add_meta_boxes', array( $this, 'meta_box_add' ) );
		add_action( 'save_post', array( $this, 'meta_box_save' ) );
		add_filter( 'the_content', array( $this, 'content_filter' ) );
		// Script
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
		add_action( 'wp_head', array( $this, 'add_ajax_library' ) );	
		// Ajax Actions
		add_action( 'wp_ajax_jmenu_add_to_cart', array( $this, 'add_to_cart' ) );
		add_action( 'wp_ajax_jmenu_clear_cart', array( $this, 'clear_cart' ) );
	}

	public function post_type_support(){
		$supports = array( 'title', 'editor', 'thumbnail', 'page-attributes' );
		add_post_type_support( 'dish', $supports );
	}

	public function post_type_register(){
		$labels = array(
			'name'               => __( 'Dishes', 'jmenu' ),
			'singular_name'      => __( 'Dish', 'jmenu' ),
			'add_new'            => __( 'Add New', 'jmenu' ),
			'add_new_item'       => __( 'Add New Dish', 'jmenu' ),
			'edit_item'          => __( 'Edit Dish', 'jmenu' ),
			'new_item'           => __( 'New Dish', 'jmenu' ),
			'all_items'          => __( 'All Dishes', 'jmenu' ),
			'view_item'          => __( 'View Dish', 'jmenu' ),
			'searh_items'        => __( 'Search Dishes', 'jmenu' ),
			'not_found'          => __( 'No dishes found', 'jmenu' ),
			'not_found_in_trash' => __( 'No dishes found in trash', 'jmenu' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'Dishes', 'jmenu' )
		);

		$supports = array( 'title', 'editor', 'thumbnail', 'page-attributes' );

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => __('dish', 'jmenu') ),
			'menu_icon'          => plugins_url( 'images/plate-icon.png', __FILE__ ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => $supports
		);

		register_post_type( 'dish', $args );
	}

	public function taxonomy_register(){
		$labels = array(
			'name'                => __( 'Sections', 'jmenu' ),
			'singular_name'       => __( 'Section', 'jmenu' ),
			'search_items'        => __( 'Search Section', 'jmenu' ),
			'all_items'           => __( 'All Sections', 'jmenu' ),
			'parent_item'         => __( 'Parent Section', 'jmenu' ),
			'parent_item_colon'   => __( 'Parent Section:', 'jmenu' ),
			'edit_item'           => __( 'Edit Section', 'jmenu' ), 
			'update_item'         => __( 'Update Section', 'jmenu' ),
			'add_new_item'        => __( 'Add New Section', 'jmenu' ),
			'new_item_name'       => __( 'New Section Name', 'jmenu' ),
			'menu_name'           => __( 'Section', 'jmenu' )
		); 	

		$args = array(
			'hierarchical'        => true,
			'labels'              => $labels,
			'show_ui'             => true,
			'show_admin_column'   => true,
			'query_var'           => true,
			'rewrite'             => array( 'slug' => __('section', 'jmenu') )
		);

  	register_taxonomy( 'section', array( 'dish' ), $args );
	}

	public function meta_box_add(){
		add_meta_box( 'jmenu_information', __( 'Dish Information', 'jmenu' ), array( $this, 'meta_box_content' ), 'dish', 'side', 'high' );
	}

	public function meta_box_content($post){
?>
<?php wp_nonce_field( plugin_basename(__FILE__), 'jmenu_meta_box' )  ?>
<?php
	$value = get_post_meta( $post->ID, '_jmenu_max_per_order', TRUE );
?>
<p>
	<label for="max_per_order"><?php _e( 'Max per order', 'jmenu' ) ?>:</label>
	<input type="text" name="max_per_order" id="max_per_order" value="<?php echo $value ?>" />
</p>
<?php
	$value = get_post_meta( $post->ID, '_jmenu_price', TRUE );
?>
<p>
	<label for="price"><?php _e( 'Price', 'jmenu' ) ?>:</label>
	<input type="text" name="price" id="price" value="<?php echo $value ?>" />
</p>

<?php
	}

	public function meta_box_save( $post_id ){
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;

		if ( !isset( $_POST['jmenu_meta_box'] ) || !wp_verify_nonce( $_POST['jmenu_meta_box'], plugin_basename( __FILE__ ) ) )
			return;

		if ( 'dish' == $_POST['post_type'] ):
			if ( !current_user_can( 'edit_page', $post_id ) ):
				return;
			endif;
		else:
			if ( !current_user_can( 'edit_post', $post_id ) ):
				return;
			endif;
		endif;


		$post_ID = $_POST['post_ID'];
		$price   = sanitize_text_field( $_POST['price'] );
		$max     = sanitize_text_field( $_POST['max_per_order'] );

		if( $price < 0 || $max < 0 )
			return;

		add_post_meta($post_ID, '_jmenu_price', $price, true)       or update_post_meta($post_ID, '_jmenu_price', $price);
		add_post_meta($post_ID, '_jmenu_max_per_order', $max, true) or update_post_meta($post_ID, '_jmenu_max_per_order', $max);
	}

	public function content_filter( $content ){
		global $post;

		if( $post->post_type == 'dish' ):
			$content .= '<form id="add-to-cart" method="post">';
			$content .= "\t".wp_nonce_field( 'add', 'jmenu_cart', true ,false );
			$content .= "\t".'<input type="hidden" name="dish_id" id="dish_id" value="'.$post->ID.'" />';
			$content .= "\t".'<p>'.__( 'Max Per Order', 'jmenu' ).': '.get_post_meta( $post->ID, '_jmenu_max_per_order', TRUE ).'<br />';
			$content .= "\t".__( 'Price', 'jmenu' ).': $'.get_post_meta( $post->ID, '_jmenu_price', TRUE ).'</p>';
			$content .= "\t"."<p>";
			$content .= "\t".'<label for="quantity">'.__( 'Quantity', 'jmenu' ).':</label>';
			
			if( get_post_meta( $post->ID, '_jmenu_max_per_order', TRUE ) > 0 )
				$content .= "\t".'<input type="number" name="quantity" id="quantity" min="1" max="'.get_post_meta( $post->ID, '_jmenu_max_per_order', TRUE ).'" value="1" size="1" />';
			else
				$content .= "\t".'<input type="number" name="quantity" id="quantity" min="1" value="1" size="1" />';
			
			$content .= "\t".'<input type="submit" name="submit" id="add-to-cart" value="'.__( 'Add to Cart', 'jmenu' ).'">';
			$content .= "\t ".__( 'or', 'jmenu' ).' ';
			$content .= "\t".'<input type="submit" name="submit" id="clear-cart" value="'.__( 'Clear Cart', 'jmenu' ).'">';
			$content .= "\t"."</p>";
			$content .= '</form>';
		endif;
		return $content;
	}

	public function scripts(){
		wp_register_script( 'jmenu-ajax', plugins_url( 'javascript/jmenu-ajax.js', __FILE__ ), array( 'jquery' ) );
		wp_enqueue_script( 'jmenu-ajax' );
	}

	public function add_ajax_library() {
		$html = '<script type="text/javascript">';
		$html .= 'var ajaxurl = "' . admin_url( 'admin-ajax.php' ) . '"';
		$html .= '</script>';

		echo $html;
	}

	public function add_to_cart(){
		if( !isset( $_POST['item'] ) || !isset( $_POST['quantity'] ) ):
			echo __( 'Dish already on the cart', 'jmenu' );
		else:
			$price = get_post_meta( $_POST['item'], '_jmenu_price', TRUE );
			$quantity = $_POST['quantity'];
			echo __( 'Dish added with price', 'jmenu' ).': $'.$price.' USD. Quantity: '.$quantity;
		endif;

		die();
	}

	public function clear_cart(){
		echo __( 'Cart cleared', 'jmenu' );

		die();
	}
}
new JA_Menu();
?>