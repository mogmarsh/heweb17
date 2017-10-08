<?php
/*
Plugin Name: HEWEB17
Plugin URI: https://github.com/mogmarsh/heweb17
Description: Used as demo plugin for 2017 HighEdWeb Presentation https://docs.google.com/presentation/d/1TjlOCezW4CFWHlO0wAOo9F0qkzBkPA_eoOxbv_v4pp0/edit?usp=sharing
Version: 0.1.0
Author: Greg Marshall/Truman State University
Author URI: http://its.truman.edu
Text Domain: heweb17
*/

$heweb17 = new heweb17;

/**
 * Class heweb17
 */
class heweb17 {

	/**
	 * heweb17 constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ) );
		add_filter( 'gettext', array( $this, 'translate_woocommerce' ), 10, 3 );
		add_filter( 'woocommerce_product_tabs', array( $this, 'woo_new_product_tab' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_meta' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'add_meta_save' ) );
		add_action( 'woocommerce_product_meta_start', array( $this, 'show_additional_attributes' ) );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'variation_settings_fields' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_settings_fields' ), 10, 2 );
		add_action( 'edit_form_advanced', array( $this, 'add_additional_editors' ) );
		add_action( 'save_post', array( $this, 'save_additional_editors' ) );
		add_action( 'woocommerce_after_variations_form', array( $this, 'show_ebook_urls' ) );
		add_action( 'admin_print_scripts-post-new.php', array( $this, 'add_admin_scripts' ), 11 );
		add_action( 'admin_print_scripts-post.php', array( $this, 'add_admin_scripts' ), 11 );
		add_action( 'woocommerce_after_template_part', array( $this, 'add_author_name' ), 10, 4 );
		add_action( 'woocommerce_before_template_part', array( $this, 'add_look_inside_link' ), 10, 4 );
		add_shortcode( 'product_category_by_year', array( $this, 'product_category_by_year' ) );
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'add_invoice_number_field' ) );
		add_action( 'woocommerce_add_to_cart_validation', array( $this, 'invoice_number_validation' ), 10, 3 );
		add_action( 'woocommerce_add_cart_item_data', array( $this, 'save_invoice_number_field' ), 10, 2 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'show_invoice_number_on_cart_and_checkout' ), 10, 2 );
		add_action( 'woocommerce_add_order_item_meta', array( $this, 'invoice_order_meta_handler' ), 1, 3 );
		add_action( 'woocommerce_after_shop_loop_item', array( $this, 'add_featureblurb' ) );
		add_filter( 'woocommerce_related_products_args', array( $this, 'remove_related_products' ), 10 );
		add_action( 'woocommerce_before_cart', array( $this, 'apply_15_percent_coupon' ) );
		add_filter( 'woocommerce_coupon_message', array( $this, 'filter_woocommerce_coupon_message' ), 10, 3 );
		add_filter( 'woocommerce_cart_totals_coupon_label', array( $this, 'filter_woocommerce_cart_totals_coupon_label' ), 10, 2 );
		add_filter( 'woocommerce_add_to_cart_handler', array( $this, 'filter_woocommerce_add_to_cart_handler' ), 10, 2 );
		add_filter( 'woocommerce_get_cart_url', array( $this, 'filter_woocommerce_get_cart_url' ), 10, 2 );
		add_filter( 'template_redirect', array( $this, 'cart_redirect' ), 10, 2 );
		add_action( 'customize_register', array( $this, 'customize_register' ) );
		add_filter( 'add_to_cart_redirect', array( $this, 'localproduct_add_to_cart_redirect' ) );
	}

	/**
     * Array that holds names of eBook Stores
	 * @var
	 */
	public static $EBOOKSTORES = array(
		'kindle' => 'Amazon Kindle®',
		'apple'  => 'Apple iBook',
		'bn'     => 'Barnes & Noble nook™',
		'google' => 'Google eBookstore',
		'kobo'   => 'Kobo Books'
	);


	/**
	 * adds the javascript to the front end
	 */
	public function add_scripts() {
		wp_enqueue_script( 'theme-scripts', get_stylesheet_directory_uri() . '/js/heweb17.js', array( 'jquery' ), '0.1' );
	}

	/**
	 * adds the javascript to admin
	 */
	function add_admin_scripts() {
		global $post;
		$post_type = $post->post_type;
		if ( 'product' == $post_type ) {
			wp_enqueue_script(
                'heweb17-admin',
                plugins_url( '/js/heweb17-admin.js', __FILE__ ),
                array( 'jquery' ) );
		}
	}

	/**
     * Changes "SKU" to "ISBN" everywhere
	 * @param $translation
	 * @param $text
	 * @param $domain
	 *
	 * @return string
	 */
	public function translate_woocommerce( $translation, $text, $domain ) {
		if ( $domain == 'woocommerce' ) {
			switch ( $text ) {
				case 'SKU':
					$translation = 'ISBN';
					break;

			}

		}
		return $translation;
	}

	/**
     * Removes some tabs and adds some new ones
	 * @param $tabs
	 *
	 * @return mixed
	 */
	public function woo_new_product_tab( $tabs ) {

		// Adds the new tab

		$tabs['contents'] = array(
			'title'    => __( 'Contents', 'woocommerce' ),
			'priority' => 20,
			'callback' => array( $this, 'contents_tab_content' )
		);

		$tabs['authors'] = array(
			'title'    => __( 'Author(s)', 'woocommerce' ),
			'priority' => 30,
			'callback' => array( $this, 'authors_tab_content' )
		);

		unset( $tabs['reviews'] );   // Remove the reviews information tab

		$tabs['heweb17reviews'] = array(
			'title'    => __( 'Reviews', 'woocommerce' ),
			'priority' => 40,
			'callback' => array( $this, 'reviews_tab_content' )
		);

		unset( $tabs['additional_information'] );   // Remove the additional information tab

		return $tabs;

	}

	/**
	 * sets the content for the "Contents" tab
	 */
	public function contents_tab_content() {
		echo '<h2>Contents</h2>';
		$content = get_post_meta( get_the_ID(), 'contents', true );
		$content = htmlspecialchars_decode( $content );
		$content = wpautop( $content );
		echo $content;
	}

	/**
	 * sets the content for the "Authors" tab
	 */
	public function authors_tab_content() {
		echo '<h2>Authors</h2>';
		$content = get_post_meta( get_the_ID(), 'authordesc', true );
		$content = htmlspecialchars_decode( $content );
		$content = wpautop( $content );
		echo $content;
	}

	/**
	 * sets the content for the "Reviews" tab
	 */
	public function reviews_tab_content() {
		echo '<h2>Reviews</h2>';
		$content = get_post_meta( get_the_ID(), 'heweb17reviews', true );
		$content = htmlspecialchars_decode( $content );
		$content = wpautop( $content );
		echo $content;
	}

	/**
	 * Adds fields for extra metadata to the edit product screen
	 */
	public function add_meta() {

		global $woocommerce, $post;
		// Text Field
		woocommerce_wp_text_input(
			array(
				'id'          => 'author',
				'label'       => __( 'Author(s)', 'woocommerce' ),
				'placeholder' => 'author(s)',
				'desc_tip'    => 'true',
				'description' => __( 'Author Name(s)', 'woocommerce' )
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'          => 'release_date',
				'label'       => __( 'Release Date', 'woocommerce' ),
				'placeholder' => date( "n/j/Y" ),
				'desc_tip'    => 'true',
				'description' => __( 'Release Date', 'woocommerce' )
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'          => 'preview_file',
				'label'       => __( 'Preview File (look inside)', 'woocommerce' ),
				'desc_tip'    => 'true',
				'description' => __( 'Upload a PDF file sample', 'woocommerce' )
			)
		);
		echo( '<input type="button" class="button custom_media" name="preview_file_button" id="preview_file_button" value="Upload/Browse"/>' );
		woocommerce_wp_checkbox(
			array(
				'id'          => 'local_product',
				'label'       => __( 'Local Product', 'woocommerce' ),
				'desc_tip'    => 'true',
				'description' => __( '(not Longleaf)', 'woocommerce' ),
				'cbvalue'     => '1'
			)
		);
	}

	/**
     * Saves the additional metadata
	 * @param $post_id
	 */
	public function add_meta_save( $post_id ) {

		// Saving Author
		$author = $_POST['author'];
		update_post_meta( $post_id, 'author', esc_attr( $author ) );
		$release_date = $_POST['release_date'];
		update_post_meta( $post_id, 'release_date', esc_attr( $release_date ) );
		$preview_file = $_POST['preview_file'];
		update_post_meta( $post_id, 'preview_file', esc_attr( $preview_file ) );
		$local_product = isset( $_POST['local_product'] );
		if ( $local_product ) {
			update_post_meta( $post_id, 'local_product', true );
		} else {
			delete_post_meta( $post_id, 'local_product' );
		}
	}

	/**
	 *
	 */
	public function show_additional_attributes() {
		global $product;
		$product->list_attributes();
		$productmeta = get_post_meta( $product->id );

		$variations = $product->get_children();
		foreach ( $variations as $variationId ) {
			$attributes = wc_get_product_variation_attributes( $variationId );
			$format     = $attributes['attribute_pa_format'];
			echo( "<div id=\"attributes_{$format}\" class=\"variation_attributes\" style=\"display: none\">" );
			$meta          = get_post_meta( $variationId );
			$pages         = $meta['_pages'][0];
			$illustrations = $meta['_illustrations'][0];
			$month         = $meta['_month'][0];
			$year          = $meta['_year'][0];
			if ( $month == '' && $year == '' ) {
				$month = date( 'n', strtotime( $productmeta['release_date'][0] ) );
				$year  = date( 'Y', strtotime( $productmeta['release_date'][0] ) );
			}
			echo( "Pages: $pages<br />" );
			if ( $illustrations ) {
				echo( $illustrations . "<br />" );
			}
			if ( $month ) {
				echo( date( 'F', mktime( 0, 0, 0, $month, 10 ) ) . " " );
			}
			if ( $year ) {
				echo( $year . "<br />" );
			}
			$variation  = wc_get_product( $variationId );
			$dimensions = $variation->get_dimensions();
			echo( $dimensions );
			echo( "</div>" );
		}
	}


	/**
	 * Create new fields for variations
	 *
	 */
	public function variation_settings_fields( $loop, $variation_data, $variation ) {
		// Number Field
		woocommerce_wp_text_input(
			array(
				'id'                => '_pages[' . $variation->ID . ']',
				'label'             => __( 'Pages', 'woocommerce' ),
				'desc_tip'          => 'true',
				'description'       => __( 'Number of pages.', 'woocommerce' ),
				'value'             => get_post_meta( $variation->ID, '_pages', true ),
				'custom_attributes' => array(
					'step' => 'any',
					'min'  => '0'
				)
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'                => '_illustrations[' . $variation->ID . ']',
				'label'             => __( 'Illustrations', 'woocommerce' ),
				'desc_tip'          => 'true',
				'description'       => __( 'Number of illustrations.', 'woocommerce' ),
				'value'             => get_post_meta( $variation->ID, '_illustrations', true ),
				'custom_attributes' => array(
					'step' => 'any',
					'min'  => '0'
				)
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'          => '_month[' . $variation->ID . ']',
				'label'       => __( 'Month', 'woocommerce' ),
				'desc_tip'    => 'true',
				'description' => __( 'Month of Release.', 'woocommerce' ),
				'value'       => get_post_meta( $variation->ID, '_month', true ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => '_year[' . $variation->ID . ']',
				'label'       => __( 'Year', 'woocommerce' ),
				'desc_tip'    => 'true',
				'description' => __( 'Year of Release.', 'woocommerce' ),
				'value'       => get_post_meta( $variation->ID, '_year', true ),
			)
		);

		foreach ( self::$EBOOKSTORES as $key => $value ) {
			woocommerce_wp_text_input(
				array(
					'id'          => '_' . $key . '[' . $variation->ID . ']',
					'label'       => $value,
					'desc_tip'    => 'true',
					'description' => 'URL of book on ' . $value,
					'value'       => get_post_meta( $variation->ID, '_' . $key, true ),
				)
			);

		}

	}

	/**
	 * Save new fields for variations
	 *
	 */
	public function save_variation_settings_fields( $post_id ) {

		// Number Field
		$pages = $_POST['_pages'][ $post_id ];
		update_post_meta( $post_id, '_pages', esc_attr( $pages ) );
		$illustrations = $_POST['_illustrations'][ $post_id ];
		update_post_meta( $post_id, '_illustrations', esc_attr( $illustrations ) );
		$month = $_POST['_month'][ $post_id ];
		update_post_meta( $post_id, '_month', esc_attr( $month ) );
		$year = $_POST['_year'][ $post_id ];
		update_post_meta( $post_id, '_year', esc_attr( $year ) );
		foreach ( self::$EBOOKSTORES as $key => $value ) {
			$postvalue = $_POST[ '_' . $key ][ $post_id ];
			update_post_meta( $post_id, '_' . $key, esc_attr( $postvalue ) );
		}
	}


	/**
     * Add rich text editor fields to the edit product page for Contents, Authors, and Reviews tabs
	 * @param $post
	 */
	public function add_additional_editors( $post ) {
		if ( $post->post_type == 'product' ) {
			echo "<div id=\"postcontent\" class=\"postbox\">";
			echo "<h2 class=\"hndle ui-sortable-handle\">Contents:</h2>";
			echo "<div class=\"inside\">";
			$content = get_post_meta( $post->ID, 'contents', true );
			wp_editor( htmlspecialchars_decode( $content ), 'contents', array( 'editor_height' => 175 ) );
			echo "</div>";
			echo "</div>";

			echo "<div id=\"postauthors\" class=\"postbox\">";
			echo "<h2 class=\"hndle ui-sortable-handle\">Author Bio(s):</h2>";
			echo "<div class=\"inside\">";
			$content = get_post_meta( $post->ID, 'authordesc', true );
			wp_editor( htmlspecialchars_decode( $content ), 'authordesc', array( 'editor_height' => 175 ) );
			echo "</div>";
			echo "</div>";

			echo "<div id=\"reviews\" class=\"postbox\">";
			echo "<h2 class=\"hndle ui-sortable-handle\">Reviews:</h2>";
			echo "<div class=\"inside\">";
			$content = get_post_meta( $post->ID, 'heweb17reviews', true );
			wp_editor( htmlspecialchars_decode( $content ), 'heweb17reviews', array( 'editor_height' => 175 ) );
			echo "</div>";
			//echo "</div>";

			//echo "<div id=\"featureblurb\" class=\"postbox\">";
			echo "<h2 class=\"hndle ui-sortable-handle\">Feature Link:</h2>";
			echo "<div class=\"inside\">";
			$content = get_post_meta( $post->ID, 'featureblurb', true );
			wp_editor( htmlspecialchars_decode( $content ), 'featureblurb', array( 'editor_height' => 175 ) );
			echo "</div>";
			echo "</div>";
		}
	}


	/**
	 * Saves the conten for the contets, authors, and reviews tabs
	 */
	public function save_additional_editors() {

		global $post;
		$post_id = $post->ID;

		if ( ! empty( $_POST['contents'] ) ) {
			$data = htmlspecialchars( $_POST['contents'] );
			update_post_meta( $post_id, 'contents', $data );
		}
		if ( ! empty( $_POST['authordesc'] ) ) {
			$data = htmlspecialchars( $_POST['authordesc'] );
			update_post_meta( $post_id, 'authordesc', $data );
		}
		if ( ! empty( $_POST['heweb17reviews'] ) ) {
			$data = htmlspecialchars( $_POST['heweb17reviews'] );
			update_post_meta( $post_id, 'heweb17reviews', $data );
		}
		if ( ! empty( $_POST['featureblurb'] ) ) {
			$data = htmlspecialchars( $_POST['featureblurb'] );
			update_post_meta( $post_id, 'featureblurb', $data );
		}


	}

	/**
	 * Shows the eBook urls on the front end
	 */
	public function show_ebook_urls() {
		global $product;
		$variations = $product->get_children();
		$ebooklinks = '';
		foreach ( $variations as $variationId ) {
			$attributes = wc_get_product_variation_attributes( $variationId );
			$format     = $attributes['attribute_pa_format'];
			if ( $format == 'ebook' ) {
				$meta = get_post_meta( $variationId );
				foreach ( self::$EBOOKSTORES as $key => $value ) {
					if ( $meta[ '_' . $key ][0] <> '' ) {
						$ebooklinks .= sprintf( '<li><a href="%s" target="_blank">%s</a></li>', $meta[ '_' . $key ][0], $value );
					}
				}
				if ( $ebooklinks <> '' ) {
					$ebooklinks = "<ul style=\"display: none\" id=\"ebooklinks\">" . $ebooklinks . "</ul>";
					echo( $ebooklinks );
				}
			}
		}
	}


	/**
     * Adds the author name(s) on the front end
	 * @param $template_name
	 * @param $template_path
	 * @param $located
	 * @param $args
	 */
	function add_author_name( $template_name, $template_path, $located, $args ) {
		global $product;
		if ( $template_name == 'single-product/title.php' ) {
			$author = get_post_meta( $product->id, 'author', true );
			echo "<h2 class=\"author_name\">$author</h2>";
		}
	}


	/**
     * Adds the Look Inside link on the front end
	 * @param $template_name
	 * @param $template_path
	 * @param $located
	 * @param $args
	 */
	function add_look_inside_link( $template_name, $template_path, $located, $args ) {
		global $product;
		if ( $template_name == 'single-product/tabs/tabs.php' ) {
			$preview_file = get_post_meta( $product->id, 'preview_file', true );
			if ( $preview_file <> '' ) {
				echo "<p style=\"padding: 5px 0\"><a href=\"$preview_file\" class=\"button add_to_cart_button product_type_variable\" target=\"_blank\">Look Inside</a></p>";

			}
		}
	}

	/**
     * Shortcode to list a product category by year
	 * @param $atts
	 *
	 * @return string
	 */
	public static function product_category_by_year( $atts ) {
		$atts = shortcode_atts( array(
			'per_page' => '12',
			'columns'  => '4',
			'orderby'  => 'title',
			'order'    => 'desc',
			'category' => '',  // Slugs
			'operator' => 'IN', // Possible values are 'IN', 'NOT IN', 'AND'.
			'year'     => '2015'
		), $atts );

		if ( ! $atts['category'] ) {
			return '';
		}

		// Default ordering args
		$query_args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'orderby'             => $atts['orderby'],
			'order'               => $atts['order'],
			'posts_per_page'      => $atts['per_page'],
			'product_cat'         => $atts['category'],
			'year'                => $atts['year'],
		);

		// Create the new query
		$loop = new WP_Query( $query_args );

		// Get products number
		$product_count = $loop->post_count;

		// If results
		if ( $product_count > 0 ) :
			echo '<ul class="products">';
			// Start the loop
			while ( $loop->have_posts() ) : $loop->the_post();
				global $product;
				global $post;
				wc_get_template_part( 'content', 'product' );
			endwhile;
			echo '</ul><!--/.products-->';
		else :
			_e( 'No product matching your criteria.' );
		endif; // endif $product_count > 0
		return ob_get_clean();
	}

	/**
	 * Adds the Invoice number field to the invoice product only
	 */
	function add_invoice_number_field() {
		if ( get_the_title() == "Invoice" ) {
			echo '<p<label for="invoice_number">Invoice Number:</label> <input type="text" name="invoice_number" id="invoice_number" value="" /></p>';
		}
	}

	/**
     * Makes sure the invoice number has been entered
	 * @param $flaq
	 * @param $product_id
	 * @param $quantity
	 *
	 * @return bool
	 */
	function invoice_number_validation( $flaq, $product_id, $quantity ) {
		if ( get_the_title( $product_id ) == "Invoice" ) {
			if ( empty( $_REQUEST['invoice_number'] ) ) {
				wc_add_notice( __( 'Please enter an Invoice Number&hellip;', 'woocommerce' ), 'error' );

				return false;
			}
		}

		return true;
	}

	/**
     * Saves the invoice number
	 * @param $cart_item_data
	 * @param $product_id
	 *
	 * @return mixed
	 */
	function save_invoice_number_field( $cart_item_data, $product_id ) {
		if ( isset( $_REQUEST['invoice_number'] ) ) {
			$cart_item_data['invoice_number'] = $_REQUEST['invoice_number'];
			/* below statement make sure every add to cart action as unique line item */
			$cart_item_data['unique_key'] = md5( microtime() . rand() );
		}

		return $cart_item_data;
	}

	/**
     * Shows the invoice number on cart and checkout
	 * @param $cart_data
	 * @param null $cart_item
	 *
	 * @return array
	 */
	function show_invoice_number_on_cart_and_checkout( $cart_data, $cart_item = null ) {
		$custom_items = array();
		/* Woo 2.4.2 updates */
		if ( ! empty( $cart_data ) ) {
			$custom_items = $cart_data;
		}
		if ( isset( $cart_item['invoice_number'] ) ) {
			$custom_items[] = array( "name" => 'Invoice Number', "value" => $cart_item['invoice_number'] );
		}

		return $custom_items;
	}

	/**
     * Saves the invoice number to the order
	 * @param $item_id
	 * @param $values
	 * @param $cart_item_key
	 */
	function invoice_order_meta_handler( $item_id, $values, $cart_item_key ) {
		if ( isset( $values['invoice_number'] ) ) {
			wc_add_order_item_meta( $item_id, "Invoice Number", $values['invoice_number'] );
		}
	}

	/**
	 * Adds the "feature blurb" to products on the homepage
	 */
	function add_featureblurb() {
		global $product;
		if ( is_front_page() ) {
			$content = get_post_meta( $product->id, 'featureblurb', true );
			if ( $content ) {
				echo( '<div class="featureblurb">' . html_entity_decode( $content ) . '</div>' );
			}
		}
	}

	/**
     * Removes related products
	 * @param $args
	 *
	 * @return array
	 */
	function remove_related_products( $args ) {
		return array();
	}


	/**
	 * Automatically adds the 15% coupon to all orders
	 */
	function apply_15_percent_coupon() {
		global $woocommerce;

		$coupon_code = '15percent'; // your coupon code here
		if ( $_POST['coupon_code'] != '' ) {
			$woocommerce->cart->remove_coupon( $coupon_code );

			return;
		}
		if ( $woocommerce->cart->has_discount( $coupon_code ) ) {
			return;
		}
		if ( count( $woocommerce->cart->get_applied_coupons() ) == 0 ) {
			$woocommerce->cart->add_discount( $coupon_code );
		}
	}

	/**
     * Changes the display of the 15% coupon success message
	 * @param $msg
	 * @param $msg_code
	 * @param $instance
	 *
	 * @return string
	 */
	function filter_woocommerce_coupon_message( $msg, $msg_code, $instance ) {
		if ( $instance->code == "15percent" ) {
			$msg = "15% discount applied successfully";
		}

		return $msg;
	}

	/**
     * Changes the display that the 15% coupon is being used
	 * @param $esc_html
	 * @param $coupon
	 *
	 * @return string
	 */
	function filter_woocommerce_cart_totals_coupon_label( $esc_html, $coupon ) {
		if ( $coupon->code == "15percent" ) {
			$esc_html = "15% Discount";
		}

		return $esc_html;
	}

	/**
     * Redirects the add to cart process to go to Longleaf
	 * @param $adding_to_cart_product_type
	 * @param $adding_to_cart
	 */
	function filter_woocommerce_add_to_cart_handler( $adding_to_cart_product_type, $adding_to_cart ) {

		$longleafurl = get_theme_mod( 'longleaf' );
		if ( $longleafurl ) {
			$product_id    = $_POST['add-to-cart'];
			$local_product = get_post_meta( $product_id, 'local_product', true );
			if ( $adding_to_cart_product_type == 'variable' ) {
				$variation_id = $_POST['variation_id'];
				$product      = new WC_Product_Variation( $variation_id );
			} else {
				$product = new WC_Product( $product_id );
			}
			if ( $local_product != "1" ) {
				$isbn        = $product->get_sku();
				$longleaf_id = substr( $isbn, 4, 8 );

				wp_redirect( trailingslashit( $longleafurl ) . "cart/add?productid=" . $longleaf_id );
				exit;
			}
		}
	}

	/**
     * Changes the cart url to point to longleaf
	 * @param $wc_get_page_permalink
	 *
	 * @return string
	 */
	function filter_woocommerce_get_cart_url( $wc_get_page_permalink ) {
		$longleafurl = get_theme_mod( 'longleaf' );
		if ( $longleafurl && WC()->cart->get_cart_contents_count() == 0 ) {
			return trailingslashit( $longleafurl );
		} else {
			return $wc_get_page_permalink;
		}
	}


	/**
	 * Changes the header to remove cart contents
	 */
	function storefront_header_cart() {
		if ( storefront_is_woocommerce_activated() ) {
			?>
            <ul id="site-header-cart" class="site-header-cart menu">
                <li class="<?php echo esc_attr( $class ); ?>">
                    <a href="<?php echo esc_url( WC()->cart->get_cart_url() ); ?>"
                       title="<?php esc_attr_e( 'View your shopping cart', 'storefront' ); ?>">
                        View your shopping cart <i class="fa fa-shopping-basket" aria-hidden="true"></i>
                    </a>
                </li>
            </ul>
			<?php
		}
	}

	/**
	 * Changes cart to redirect to Longleaf
	 */
	function cart_redirect() {
		global $post;
		if ( $post->post_name == 'cart' ) {
			$longleafurl = get_theme_mod( 'longleaf' );
			if ( $longleafurl && WC()->cart->get_cart_contents_count() == 0 ) {
				wp_redirect( trailingslashit( $longleafurl ) );
				exit;
			}
		}
	}

	/**
     * Adds a settings field for the longleaf url
	 * @param $wp_customize
	 */
	public function customize_register( $wp_customize ) {
		$wp_customize->add_section(
			'heweb17_settings',
			array(
				'title'       => 'HEWeb17 Settings',
				'description' => 'Settings for HEWeb17.',
				'priority'    => 5,
			)
		);


		$wp_customize->add_setting(
			'longleaf',
			array(
				'sanitize_callback' => 'esc_url'
			)
		);

		$wp_customize->add_control(
			'longleaf',
			array(
				'type'    => 'text',
				'label'   => 'Longleaf URL',
				'section' => 'heweb17_settings',
			)
		);
	}

	/**
     * Sets local products to use regular checkout url
	 * @return mixed
	 */
	function localproduct_add_to_cart_redirect() {
		global $woocommerce;
		$checkout_url = $woocommerce->cart->get_checkout_url();

		return $checkout_url;
	}

}