<?php
/**
 * Plugin Name: Show Stock Status for WooCommerce
 * Description: Show the “Stock Quantity” for each product in the shop, category and archive pages.
 * Author: Bright Plugins
 * Version: 1.0.5
 * Author URI: https://brightplugins.com/
 * Text Domain: woo-show-stock
 * Domain Path:  /languages/
 * Requires PHP: 7.2.0
 * Requires at least: 4.9
 * Tested up to: 6.5.3
 * WC tested up to: 8.8.3
 * Requires Plugins: woocommerce
 * WC requires at least: 3.4
 */

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);
function brightvessel_woocommerce_show_stock( $return_as_value = 0 ) {
	global $product;

	$low_stock_notify = wc_get_low_stock_amount( $product ); // get low stock from product

	// check if product type is not variable
	if ( ! $product->is_type( 'variable' ) ) {
		if ( $product->get_manage_stock() ) { // if manage stock is enabled

			if ( ! $product->is_in_stock() ) {
				echo "<div class='remaining-out-stock'>" . esc_html__( ' Out of stock', 'woo-show-stock' ) . '</div>';
				return;
			}

			$stockNum = $product->get_stock_quantity();
			if ( $stockNum <= $low_stock_notify ) { // if stock is low
				if ( $return_as_value ) {
					return $stockNum; }
				return printf(
					/* translators: number of stock . */

					__( "<div class='remaining-low-stock'> Only %s left in stock</div>", 'woo-show-stock' ),
					$stockNum
				);
			}
			if ( $return_as_value ) {
				return $stockNum; }
			return printf(
				/* translators: number of stock . */
				__( "<div class='remaining'> %s left in stock </div>", 'woo-show-stock' ),
				$stockNum
			);
		}
	} elseif ( $product->get_manage_stock() ) {
		// if manage stock is enabled
			$product_variations = $product->get_available_variations();
			$display_type       = 'sumboth';
			$stock_str          = '';
		if ( ! empty( get_post_meta( $product->get_id(), '_woostock_display_type' ) ) ) {
			$display_type = get_post_meta( $product->get_id(), '_woostock_display_type', true );
			if ( 'product' == $display_type || 'sumboth' == $display_type ) {
				$stock     = $product->get_stock_quantity();
				$stock_str = '<br>Product: ' . $stock;
			} else {
				$stock = 0;
			}
		} else {
			$stock = $product->get_stock_quantity();
		}

		if ( 'variants' == $display_type || 'sumboth' == $display_type ) {
			$stock_vars = 0;
			foreach ( $product_variations as $variation ) {
				// check if variant controles stock
				$is_stock_manage = get_post_meta( $variation['variation_id'] ?? 0, '_manage_stock', true );
				if ( 'yes' == $is_stock_manage ) {
					if ( empty( $variation['max_qty'] ) ) {
						$variation['max_qty'] = 0;
					}
					$stock      += absint( $variation['max_qty'] );
					$stock_vars += absint( $variation['max_qty'] );
				}
			}
			$stock_str .= '<br>Variants: ' . $stock_vars;
		}

		if ( $stock > 0 ) {
			if ( $stock <= $low_stock_notify ) { // if stock is low
				if ( $return_as_value ) {
					return 'Total: ' . $stock . $stock_str; }
				return printf(
					/* translators: number of stock . */
					__( "<div class='remaining-low-stock bpss-low-stock'>Only %s left in stock </div>", 'woo-show-stock' ),
					$stock
				);
			}

			if ( $return_as_value ) {
				return 'Total: ' . $stock . $stock_str; }
			return printf(
				/* translators: number of stock . */
				__( "<div class='remaining bpss-remaining'> %s left in stock </div>", 'woo-show-stock' ),
				$stock
			);
		}
	} else {

		$product_variations = $product->get_available_variations();
		$stock              = 0; // $product->get_stock_quantity();
		foreach ( $product_variations as $variation ) {
			// check if variant controles stock
			$is_stock_manage = get_post_meta( $variation['variation_id'] ?? 0, '_manage_stock', true );
			if ( 'yes' == $is_stock_manage ) {
				if ( empty( $variation['max_qty'] ) ) {
					$variation['max_qty'] = 0;
				}
				$stock += absint( $variation['max_qty'] );
			}
		}

		if ( $stock > 0 ) {
			if ( $stock <= $low_stock_notify ) { // if stock is low
				if ( $return_as_value ) {
					return $stock; }
				return printf(
					/* translators: number of stock . */
					__( "<div class='remaining-low-stock bpss-low-stock'>Only %s left in stock</div>", 'woo-show-stock' ),
					$stock
				);
			}
			if ( $return_as_value ) {
				return $stock; }
			return printf(
				/* translators: number of stock . */
				__( "<div class='remaining bpss-remaining'> %s left in stock</div>", 'woo-show-stock' ),
				$stock
			);
		}
	}
}

if ( 'yes' === get_option( 'wc_always_show_stock' ) && null !== get_option( 'wc_show_stock_where' ) ) {
	add_action( get_option( 'wc_show_stock_where' ), 'brightvessel_woocommerce_show_stock', 10 );
}

// Add settings to the specific section we created before

add_filter( 'woocommerce_get_settings_products', 'brightvessel_woocommerce_show_stock_all_settings', 10, 2 );
/**
 * @param $settings
 * @param $current_section
 * @return mixed
 */
function brightvessel_woocommerce_show_stock_all_settings( $settings, $current_section ) {
	// Check the current section is what we want
	if ( 'inventory' === $current_section ) {
		$settings[] = array(
			'name' => __( 'Stock Settings', 'woo-show-stock' ),
			'type' => 'title',
			'desc' => __( 'The following options are used to configure how to show your stock', 'woo-show-stock' ),
			'id'   => 'stockoptions',
		);

		$settings[] = array(
			'name' => __( 'Always show stock', 'woo-show-stock' ),
			'type' => 'checkbox',
			'desc' => __( 'Always show available stock', 'woo-show-stock' ),
			'id'   => 'wc_always_show_stock',
		);

		$settings[] = array(
			'name' => __( 'Show detailed stock on admin products page', 'woo-show-stock' ),
			'type' => 'checkbox',
			'desc' => __( 'When product is variable it shows the Stock on product level and the Stock on variant level', 'woo-show-stock' ),
			'id'   => 'woostock_show_detailed_admin',
		);

		$settings[] = array(
			'name'    => __( 'Stock position', 'woo-show-stock' ),
			'type'    => 'select',

			'options' => array(
				'woocommerce_after_shop_loop_item'        => __( 'After shop loop (recommended)', 'woo-show-stock' ),
				'woocommerce_after_shop_loop_item_title'  => __( 'After title', 'woo-show-stock' ),
				'woocommerce_before_shop_loop_item_title' => __( 'Before title', 'woo-show-stock' ),
			),
			'desc'    => __( 'Where the actual stock should be displayed', 'woo-show-stock' ),
			'id'      => 'wc_show_stock_where',
		);
		// todo: add new settings for for hook priroty
		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'wc_settings_tab_stock',
		);
	}

	return $settings;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ) . '', 'bpwcssPluginMeta' );
/**
 * links in Plugin Meta
 *
 * @param  [array] $links
 * @return void
 */
function bpwcssPluginMeta( $links ) {
	$row_meta = array(
		'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=products&section=inventory#stockoptions-description' ) . '">Stock Settings</a>',
		'support'  => '<a style="color:red;" target="_blank" href="' . esc_url( 'https://brightplugins.com/support' ) . '">Support</a>',

	);
	return array_merge( $links, $row_meta );
}

register_activation_hook( __FILE__, 'bpwcssPluginActivation' );
function bpwcssPluginActivation() {
	// set plugin instalation date
	$installed = get_option( 'bpwcss_installed' );
	if ( ! $installed ) {
		update_option( 'bpwcss_installed', date( 'Y/m/d' ) );
	}
}

function brightvessel_woocommerce_show_stock_check_notice() {
	if ( isset( $_GET['bvsclose'] ) && 'true' === $_GET['bvsclose'] ) {
		add_option( 'bvsclose', 1 );
	}
	if ( 1 !== (int) ( get_option( 'bvsclose' ) ) ) {
		add_action( 'admin_notices', 'brightvessel_woocommerce_show_stock_check_notice' );
	}
	// for review dismiss / check
	if ( isset( $_GET['bpwss-review-dismiss'] ) && $_GET['bpwss-review-dismiss'] == 1 ) {
		update_option( 'bpwss-review-dismiss', 1 );
	}
	if ( isset( $_GET['bpwss-later-dismiss'] ) && $_GET['bpwss-later-dismiss'] == 1 ) {
		set_transient( 'bpwss-later-dismiss', 1, 2 * DAY_IN_SECONDS );
	}
}
add_action( 'admin_init', 'brightvessel_woocommerce_show_stock_check_notice' );

if ( time() > strtotime( get_option( 'bpwcss_installed' ) . ' + 3 Days' ) ) {
	add_action( 'admin_notices', 'bpShowStockReview' );
}

/**
 * @return null
 */
function bpShowStockReview() {
	$dismissPram     = array( 'bpwss-review-dismiss' => '1' );
	$bpwssMaybeLater = array( 'bpwss-later-dismiss' => '1' );

	if ( get_option( 'bpwss-review-dismiss' ) || get_transient( 'bpwss-later-dismiss' ) ) {
		return;
	}?>
		<div class="notice ciplugin-review">
		<p style="font-size:15px;"><img draggable="false" class="emoji" alt="🎉" src="https://s.w.org/images/core/emoji/11/svg/1f389.svg"><strong style="font-size: 19px; margin-bottom: 5px; display: inline-block;" ><?php echo __( 'Thanks for using Show stock for WooCommerce.', 'wpgs' ); ?></strong><br> <?php _e( 'If you can spare a minute, please help us by leaving a 5 star review on WordPress.org.', 'wpgs' ); ?></p>
		<p class="dfwc-message-actions">
			<a style="margin-right:5px;" href="https://wordpress.org/support/plugin/woo-show-stock/reviews/#new-post" target="_blank" class="button button-primary button-primary"><?php _e( 'Happy To Help', 'wpgs' ); ?></a>
			<a style="margin-right:5px;" href="<?php echo esc_url( add_query_arg( $bpwssMaybeLater ) ); ?>" class="button button-alt"><?php _e( 'Maybe later', 'wpgs' ); ?></a>
			<a href="<?php echo esc_url( add_query_arg( $dismissPram ) ); ?>" class="dfwc-button-notice-dismiss button button-link"><?php _e( 'Hide Notification', 'wpgs' ); ?></a>
		</p>
		</div>
		<?php
}

// add_action( 'woocommerce_product_after_variable_attributes', 'wooshow_custom_variations_fields', 10, 3 ); // After all Variation fields

// Inventory tab
add_action( 'woocommerce_product_options_stock_status', 'wooshow_custom_simple_fields' );

// add_action( 'woocommerce_save_product_variation', 'wooshow_custom_variations_fields_save', 10, 2 );
add_action( 'woocommerce_process_product_meta', 'wooshow_custom_simple_fields_save', 10, 2 );



function wooshow_custom_simple_fields() {
	$display_type = get_post_meta( get_the_ID(), '_woostock_display_type', true );
	if ( empty( $display_type ) ) {
		$display_type = 'sumboth';
	}
	woocommerce_wp_radio(
		array(
			'id'          => '_woostock_display_type',
			'label'       => __( 'Show stock display type', 'woo-show-stock' ),
			'default'     => 'product',
			'options'     => array(
				'product'  => __( 'Display product level stock quantity', 'woo-show-stock' ),
				'variants' => __( 'Display the sum of all variants stock', 'woo-show-stock' ),
				'sumboth'  => __( 'Product stock + Variants stock (default)', 'woo-show-stock' ),
			),
			'desc_tip'    => true,
			'description' => __( 'This setting is only applied when a variable product uses both stock management (product level and variant level at same time). With both levels enabled the stock quantity can result in an inconsistent value. <br>So choose witch display type you would like to show when the product has both systems enabled.', 'woo-show-stock' ),
			'value'       => $display_type,
		)
	);
}

function wooshow_custom_simple_fields_save( $post_id ) {
	if ( isset( $_POST['_woostock_display_type'] ) && ! empty( $_POST['_woostock_display_type'] ) ) {
		$display_type = $_POST['_woostock_display_type'];
		$product      = wc_get_product( $post_id );
		$product->update_meta_data( '_woostock_display_type', $display_type );
		$product->save();
	}
}

// Admin product list: is_in_stock
function woostock_filter_woocommerce_admin_stock_html( $stock_html, $product ) {
	// Condition
	if ( $product->is_type( 'variable' ) ) {
		if ( ! empty( get_option( 'woostock_show_detailed_admin' ) ) && 'yes' == get_option( 'woostock_show_detailed_admin' ) ) {
			$stock_html .= '<br><mark class="someclass" style="background:transparent none; font-weight:400; line-height:1;">' . brightvessel_woocommerce_show_stock( $return_as_value = 1 ) . '</mark>';
		}
	}

	return $stock_html;
}
add_filter( 'woocommerce_admin_stock_html', 'woostock_filter_woocommerce_admin_stock_html', 10, 2 );

