<?php
/**
 * Plugin Name: Easy Digital Downloads - Maximum Discount Allowed
 * Plugin URI: https://wisdomplugin.com
 * Description: Apply maximum discount amount to checkout cart in Easy Digital Downloads.
 * Author: Brian Batt
 * Author URI: https://wisdomplugin.com
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Main class.
 */
final class EDD_Max_Discount {

	/**
	 * The single instance of the class.
	 */
	protected static $_instance = null;

	/**
	 * Main Instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->edd_hooks();
	}

	/**
	 * Hook into actions and filters.
	 */
	public function edd_hooks() {
		// Add settings.
		add_action( 'edd_add_discount_form_before_products', array( $this, 'edd_add_discount_form_before_products' ) );
		add_action( 'edd_edit_discount_form_before_products', array( $this, 'edd_edit_discount_form_before_products' ), 10, 2 );

		// Save settings.
		add_filter( 'edd_insert_discount', array( $this, 'edd_save_discount' ) );
		add_filter( 'edd_update_discount', array( $this, 'edd_save_discount' ) );

		// Hooks.
		add_filter( 'edd_get_cart_item_discounted_amount', array( $this, 'edd_get_cart_item_discounted_amount' ), 10, 4 );
		add_filter( 'edd_get_cart_discount_html', array( $this, 'edd_get_cart_discount_html' ), 10, 4 );
	}

	/**
	 * Add maximum discount settings.
	 */
	public function edd_add_discount_form_before_products() {
		?>
		<tr>
			<th scope="row" valign="top">
				<label for="edd-max-discount-amount"><?php _e( 'Maximum discount allowed', 'easy-digital-downloads' ); ?></label>
			</th>
			<td>
				<input type="number" min="0" step="1" max="1000" id="edd-max-discount-amount" name="max_discount_amount" value="" />
				<p class="description"><?php _e( 'This is the maximum allowed discount for this code. Enter a flat number between 1 and 1000.', 'easy-digital-downloads' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Add max amount setting to edit discount screen.
	 */
	public function edd_edit_discount_form_before_products( $discount_id, $discount ) {

		$max_discount = get_post_meta( $discount_id, '_edd_discount_max_discount', true );

		?>
		<tr>
			<th scope="row" valign="top">
				<label for="edd-max-discount-amount"><?php _e( 'Maximum discount allowed', 'easy-digital-downloads' ); ?></label>
			</th>
			<td>
				<input type="number" min="0" step="1" max="1000" id="edd-max-discount-amount" name="max_discount_amount" value="<?php echo esc_attr( $max_discount ); ?>" style="width: 80px;"/>
				<p class="description"><?php _e( 'This is the maximum allowed discount for this code. Enter a flat number between 1 and 1000.', 'easy-digital-downloads' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Allow EDD to save the maximum discount setting.
	 */
	public function edd_save_discount( $meta ) {

		// Sanitized input - security standards.
		$max_discount = isset( $_POST[ 'max_discount_amount' ] ) ? sanitize_text_field( $_POST[ 'max_discount_amount' ] ) : '';

		$meta[ 'max_discount' ] = $max_discount;

		return $meta;
	}

	/**
	 * Get max discounted amount by checking discounts.
	 */
	public function edd_get_cart_item_discounted_amount( $discounted_price, $discounts, $item, $price ) {

		// Loop through discounts.
		if ( $discounts ) {
			foreach( $discounts as $discount_code ) {

				// Get discount meta by code.
				$discount = edd_get_discount_by_code( $discount_code );
				if ( empty( $discount ) ) {
					continue;
				}

				// If the discount is larger than max discount. Apply max discount amount instead.
				$max_amount = get_post_meta( $discount->ID, '_edd_discount_max_discount', true );
				if ( $max_amount ) {
					$difference = $price - $discounted_price;
					if ( $difference > $max_amount ) {
						$discounted_price = $price - $max_amount;
					}
				}
			}
		}

		return $discounted_price;
	}

	/**
	 * Change the HTML for max discount - remove % and add a flat discount.
	 */
	public function edd_get_cart_discount_html( $discount_html, $discount, $rate, $remove_url ) {

		$thediscount = edd_get_discount_by_code( $discount );

		if ( empty( $thediscount ) ) {
			return $discount_html;
		}

		// Get the max discount amount if available.
		$max_amount = get_post_meta( $thediscount->ID, '_edd_discount_max_discount', true );
		if ( $max_amount ) {
			$rate = edd_currency_filter( edd_format_amount( edd_get_cart_discounted_amount() ) );
			$discount_html = '';
			$discount_html .= "<span class=\"edd_discount\">\n";
				$discount_html .= "<span class=\"edd_discount_rate\">$discount&nbsp;&ndash;&nbsp;$rate</span>\n";
				$discount_html .= "<a href=\"$remove_url\" data-code=\"$discount\" class=\"edd_discount_remove\"></a>\n";
			$discount_html .= "</span>\n";
		}

		return $discount_html;
	}

}

/**
 * Main instance.
 */
function edd_max_discount() {
	return EDD_Max_Discount::instance();
}

// Global for backwards compatibility.
$GLOBALS[ 'edd_max_discount' ] = edd_max_discount();