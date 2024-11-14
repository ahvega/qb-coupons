<?php

/**
 * Plugin Name: QB Coupons
 * Description: Maneja la generación y uso de cupones para suscripciones anuales.
 * Version: 1.0
 * Author: Tu Adalberto H. Vega
 * Text Domain: qb-coupons
 * Domain Path: /languages
 */


if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * Main plugin class for handling coupon generation and management for annual subscriptions.
 *
 * @since 1.0.0
 */
class QB_Coupons {
	/**
	 * Constructor. Sets up action hooks and shortcode.
	 */
	public function __construct() {
		add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
		add_action('woocommerce_order_status_completed', array($this, 'handle_annual_subscription_purchase'));
		add_shortcode('user_coupons', array($this, 'display_user_coupons_shortcode'));

		error_log('QB Coupons plugin initialized');
	}

	/**
	 * Loads the plugin text domain for translations.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain('qb-coupons', false, dirname(plugin_basename(__FILE__)) . '/languages/');
		error_log('QB Coupons textdomain loaded');
	}

	/**
	 * Generates 12 monthly coupons for an annual subscription.
	 *
	 * @param int $user_id The ID of the user to generate coupons for.
	 * @return array Array of generated coupon codes.
	 */
	public function generate_annual_subscription_coupons($user_id) {
		error_log("Generating coupons for user ID: $user_id");
		$coupons = array();
		for ($i = 0; $i < 12; $i++) {
			$coupon_code = 'ANNUAL_' . $user_id . '_' . uniqid();
			$coupon = array(
				'post_title' => $coupon_code,
				'post_content' => '',
				'post_status' => 'publish',
				'post_author' => 1,
				'post_type' => 'shop_coupon'
			);

			$new_coupon_id = wp_insert_post($coupon);
			error_log("Created coupon with ID: $new_coupon_id");

			update_post_meta($new_coupon_id, 'discount_type', 'fixed_cart');
			update_post_meta($new_coupon_id, 'coupon_amount', '4.99');
			update_post_meta($new_coupon_id, 'individual_use', 'yes');
			update_post_meta($new_coupon_id, 'usage_limit', '1');
			update_post_meta($new_coupon_id, 'customer_email', array(get_userdata($user_id)->user_email));

			// Limitar el cupón a la categoría "Tema Ecológico"
			$category = get_term_by('name', 'Tema Ecológico', 'product_cat');
			if ($category) {
				update_post_meta($new_coupon_id, 'product_categories', array($category->term_id));
				error_log("Applied category restriction to coupon");
			} else {
				error_log("Warning: 'Tema Ecológico' category not found");
			}

			$coupons[] = $coupon_code;
		}
		error_log("Generated coupons: " . print_r($coupons, true));
		return $coupons;
	}

	/**
	 * Handles the completion of an annual subscription purchase.
	 * Generates and assigns coupons to the user if they purchased an annual subscription.
	 *
	 * @param int $order_id The ID of the completed order.
	 */
	public function handle_annual_subscription_purchase($order_id) {
		error_log("Handling order ID: $order_id");
		$order = wc_get_order($order_id);
		$user_id = $order->get_user_id();
		error_log("Order user ID: $user_id");

		// Verifica si la orden contiene una suscripción anual
		$is_annual_subscription = false;
		foreach ($order->get_items() as $item) {
			$product = $item->get_product();
			if ($product && $product->is_type('subscription') && $product->get_meta('_subscription_period') === 'year') {
				$is_annual_subscription = true;
				error_log("Annual subscription found in order");
				break;
			}
		}

		if ($is_annual_subscription) {
			$coupons = $this->generate_annual_subscription_coupons($user_id);
			update_user_meta($user_id, 'annual_subscription_coupons', $coupons);
			error_log("Coupons saved to user meta");
		} else {
			error_log("No annual subscription found in order");
		}
	}

	/**
	 * Shortcode callback that displays a user's available coupons.
	 * Includes styling and JavaScript for coupon display and copy functionality.
	 *
	 * @return string HTML output for displaying coupons.
	 */
	public function display_user_coupons_shortcode() {
		error_log('display_user_coupons_shortcode called');

		if (!is_user_logged_in()) {
			error_log('User not logged in');
			return esc_html__('Please login to view your coupons.', 'qb-coupons');
		}

		$user_id = get_current_user_id();
		error_log("Current user ID: $user_id");

		$coupons = get_user_meta($user_id, 'annual_subscription_coupons', true);
		error_log('User coupons: ' . print_r($coupons, true));

		if (!$coupons || empty($coupons)) {
			error_log('No coupons found for user');
			return esc_html__('You don\'t have any available coupons.', 'qb-coupons');
		}

		$output = '<div class="qb-coupons-container">';
		$output .= '<h2>' . esc_html__('Your Available Coupons', 'qb-coupons') . '</h2>';
		$output .= '<p>' . esc_html__('Click on the coupon code to copy it. You can then paste it in the coupon field during checkout.', 'qb-coupons') . '</p>';
		$output .= '<div class="qb-coupons-list">';
		
		foreach ($coupons as $coupon) {
			$coupon_id = wc_get_coupon_id_by_code($coupon);
			$usage_count = get_post_meta($coupon_id, 'usage_count', true);
			error_log("Coupon $coupon (ID: $coupon_id) usage count: $usage_count");
			
			if ($usage_count == 0) {
				$output .= sprintf(
					'<div class="qb-coupon-item">
						<span class="qb-coupon-code" data-coupon="%1$s">%1$s</span>
						<button class="copy-coupon" data-coupon="%1$s">%2$s</button>
						<span class="copy-feedback" style="display: none;">%3$s</span>
					</div>',
					esc_attr($coupon),
					esc_html__('Copy', 'qb-coupons'),
					esc_html__('Copied!', 'qb-coupons')
				);
			}
		}
		
		$output .= '</div>';
		$output .= '<p class="qb-coupons-note">' . esc_html__('Note: Coupons are valid only for products in the "Ecological Theme" category.', 'qb-coupons') . '</p>';
		$output .= '</div>';

		// Add styles and JavaScript
		$output .= '
		<style>
			.qb-coupons-container {
				max-width: 600px;
				margin: 20px auto;
				padding: 20px;
			}
			.qb-coupons-list {
				margin: 20px 0;
			}
			.qb-coupon-item {
				display: flex;
				align-items: center;
				margin: 10px 0;
				padding: 10px;
				background: #f8f8f8;
				border-radius: 4px;
			}
			.qb-coupon-code {
				font-family: monospace;
				font-size: 1.1em;
				padding: 8px 12px;
				background: #fff;
				border: 1px dashed #ccc;
				margin-right: 10px;
				border-radius: 3px;
				cursor: pointer;
			}
			.copy-coupon {
				padding: 6px 12px;
				background: #4CAF50;
				color: white;
				border: none;
				border-radius: 3px;
				cursor: pointer;
				margin-right: 10px;
			}
			.copy-coupon:hover {
				background: #45a049;
			}
			.copy-feedback {
				color: #4CAF50;
				font-size: 0.9em;
			}
			.qb-coupons-note {
				font-size: 0.9em;
				color: #666;
				margin-top: 20px;
			}
		</style>
		<script>
		jQuery(document).ready(function($) {
			function copyToClipboard(text) {
				return navigator.clipboard.writeText(text).then(function() {
					return true;
				}).catch(function(err) {
					console.error("Could not copy text: ", err);
					return false;
				});
			}

			$(".qb-coupon-code, .copy-coupon").on("click", function(e) {
				e.preventDefault();
				const couponCode = $(this).data("coupon");
				const couponItem = $(this).closest(".qb-coupon-item");
				const feedback = couponItem.find(".copy-feedback");

				copyToClipboard(couponCode).then(function(success) {
					if (success) {
						feedback.fadeIn().delay(1500).fadeOut();
					}
				});
			});
		});
		</script>';

		error_log('Shortcode output generated');
		return $output;
	}
}

new QB_Coupons();