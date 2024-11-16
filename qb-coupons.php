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
		
		// Add admin menu and settings
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_init', array($this, 'register_settings'));
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));

		error_log('QB Coupons plugin initialized');
	}

	/**
	 * Add settings link to plugin listing
	 */
	public function add_settings_link($links) {
		$settings_link = '<a href="' . admin_url('admin.php?page=qb-coupons-settings') . '">' . __('Settings', 'qb-coupons') . '</a>';
		array_unshift($links, $settings_link);
		return $links;
	}

	/**
	 * Add admin menu page
	 */
	public function add_admin_menu() {
		add_menu_page(
			__('QB Coupons Settings', 'qb-coupons'),
			__('QB Coupons', 'qb-coupons'),
			'manage_options',
			'qb-coupons-settings',
			array($this, 'display_admin_page'),
			'dashicons-tickets',
			55
		);
	}

	/**
	 * Register plugin settings
	 */
	public function register_settings() {
		register_setting('qb_coupons_settings', 'qb_coupons_options', array($this, 'sanitize_settings'));

		add_settings_section(
			'qb_coupons_main_section',
			__('Coupon Generation Settings', 'qb-coupons'),
			array($this, 'section_callback'),
			'qb-coupons-settings'
		);

		// Discount Type
		add_settings_field(
			'discount_type',
			__('Discount Type', 'qb-coupons'),
			array($this, 'dropdown_callback'),
			'qb-coupons-settings',
			'qb_coupons_main_section',
			array(
				'id' => 'discount_type',
				'options' => array(
					'fixed_cart' => __('Fixed cart discount', 'qb-coupons'),
					'percent' => __('Percentage discount', 'qb-coupons'),
					'fixed_product' => __('Fixed product discount', 'qb-coupons')
				)
			)
		);

		// Discount Amount
		add_settings_field(
			'discount_amount',
			__('Discount Amount', 'qb-coupons'),
			array($this, 'number_callback'),
			'qb-coupons-settings',
			'qb_coupons_main_section',
			array(
				'id' => 'discount_amount',
				'step' => '0.01',
				'min' => '0'
			)
		);

		// Number of Coupons
		add_settings_field(
			'coupon_quantity',
			__('Number of Coupons', 'qb-coupons'),
			array($this, 'number_callback'),
			'qb-coupons-settings',
			'qb_coupons_main_section',
			array(
				'id' => 'coupon_quantity',
				'step' => '1',
				'min' => '1'
			)
		);

		// Subscription Product
		add_settings_field(
			'subscription_product',
			__('Subscription Product', 'qb-coupons'),
			array($this, 'subscription_products_callback'),
			'qb-coupons-settings',
			'qb_coupons_main_section'
		);

		// Individual Use
		add_settings_field(
			'individual_use',
			__('Individual Use Only', 'qb-coupons'),
			array($this, 'checkbox_callback'),
			'qb-coupons-settings',
			'qb_coupons_main_section',
			array('id' => 'individual_use')
		);

		// Product Categories
		add_settings_field(
			'product_categories',
			__('Product Categories', 'qb-coupons'),
			array($this, 'categories_callback'),
			'qb-coupons-settings',
			'qb_coupons_main_section'
		);
	}

	/**
	 * Display the admin settings page
	 */
	public function display_admin_page() {
		if (!current_user_can('manage_options')) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields('qb_coupons_settings');
				do_settings_sections('qb-coupons-settings');
				submit_button(__('Save Settings', 'qb-coupons'));
				?>
			</form>
		</div>
		<?php
	}

	// Field Callbacks
	public function section_callback($args) {
		?>
		<p><?php _e('Configure the settings for automatic coupon generation.', 'qb-coupons'); ?></p>
		<?php
	}

	public function dropdown_callback($args) {
		$options = get_option('qb_coupons_options');
		$value = isset($options[$args['id']]) ? $options[$args['id']] : '';
		?>
		<select name="qb_coupons_options[<?php echo esc_attr($args['id']); ?>]">
			<?php foreach ($args['options'] as $key => $label) : ?>
				<option value="<?php echo esc_attr($key); ?>" <?php selected($value, $key); ?>>
					<?php echo esc_html($label); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function number_callback($args) {
		$options = get_option('qb_coupons_options');
		$value = isset($options[$args['id']]) ? $options[$args['id']] : '';
		?>
		<input type="number" 
			   name="qb_coupons_options[<?php echo esc_attr($args['id']); ?>]"
			   value="<?php echo esc_attr($value); ?>"
			   step="<?php echo esc_attr($args['step']); ?>"
			   min="<?php echo esc_attr($args['min']); ?>">
		<?php
	}

	public function checkbox_callback($args) {
		$options = get_option('qb_coupons_options');
		$checked = isset($options[$args['id']]) ? $options[$args['id']] : 0;
		?>
		<input type="checkbox" 
			   name="qb_coupons_options[<?php echo esc_attr($args['id']); ?>]"
			   value="1" 
			   <?php checked(1, $checked); ?>>
		<?php
	}

	public function subscription_products_callback() {
		$options = get_option('qb_coupons_options');
		$selected = isset($options['subscription_product']) ? $options['subscription_product'] : '';
		
		$args = array(
			'post_type' => 'product',
			'posts_per_page' => -1,
			'tax_query' => array(
				array(
					'taxonomy' => 'product_type',
					'field' => 'slug',
					'terms' => 'subscription'
				)
			)
		);
		
		$subscription_products = get_posts($args);
		?>
		<select name="qb_coupons_options[subscription_product]">
			<option value=""><?php _e('Select a subscription product', 'qb-coupons'); ?></option>
			<?php foreach ($subscription_products as $product) : ?>
				<option value="<?php echo esc_attr($product->ID); ?>" <?php selected($selected, $product->ID); ?>>
					<?php echo esc_html($product->post_title); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function categories_callback() {
		$options = get_option('qb_coupons_options');
		$selected_cats = isset($options['product_categories']) ? $options['product_categories'] : array();
		
		$categories = get_terms(array(
			'taxonomy' => 'product_cat',
			'hide_empty' => false,
		));
		
		foreach ($categories as $category) {
			?>
			<label>
				<input type="checkbox" 
					   name="qb_coupons_options[product_categories][]" 
					   value="<?php echo esc_attr($category->term_id); ?>"
					   <?php checked(in_array($category->term_id, $selected_cats)); ?>>
				<?php echo esc_html($category->name); ?>
			</label><br>
			<?php
		}
	}

	/**
	 * Sanitize settings before saving
	 */
	public function sanitize_settings($input) {
		$sanitized = array();
		
		if (isset($input['discount_type'])) {
			$sanitized['discount_type'] = sanitize_text_field($input['discount_type']);
		}
		
		if (isset($input['discount_amount'])) {
			$sanitized['discount_amount'] = floatval($input['discount_amount']);
		}
		
		if (isset($input['coupon_quantity'])) {
			$sanitized['coupon_quantity'] = intval($input['coupon_quantity']);
		}
		
		if (isset($input['subscription_product'])) {
			$sanitized['subscription_product'] = intval($input['subscription_product']);
		}
		
		$sanitized['individual_use'] = isset($input['individual_use']) ? 1 : 0;
		
		if (isset($input['product_categories']) && is_array($input['product_categories'])) {
			$sanitized['product_categories'] = array_map('intval', $input['product_categories']);
		}
		
		return $sanitized;
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
		$options = get_option('qb_coupons_options');
		$quantity = isset($options['coupon_quantity']) ? $options['coupon_quantity'] : 12;
		
		error_log("Generating coupons for user ID: $user_id");
		$coupons = array();
		
		for ($i = 0; $i < $quantity; $i++) {
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

			// Apply settings
			update_post_meta($new_coupon_id, 'discount_type', $options['discount_type'] ?? 'fixed_cart');
			update_post_meta($new_coupon_id, 'coupon_amount', $options['discount_amount'] ?? '4.99');
			update_post_meta($new_coupon_id, 'individual_use', $options['individual_use'] ?? 'yes');
			update_post_meta($new_coupon_id, 'usage_limit', '1');
			update_post_meta($new_coupon_id, 'customer_email', array(get_userdata($user_id)->user_email));

			if (!empty($options['product_categories'])) {
				update_post_meta($new_coupon_id, 'product_categories', $options['product_categories']);
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