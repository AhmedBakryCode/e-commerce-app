<?php
/**
 * Front-end promo popup for pro theme upgrade.
 *
 * Shown bottom-right on the front end to super admins only.
 * Closing it hides it for 48 hours per user (persisted via user meta).
 * The snooze is reset whenever the theme is activated or deactivated.
 *
 * @package Cosmetics Shop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'COSMETICS_SHOP_POPUP_META_KEY', 'cosmetics_shop_popup_dismissed' );
define( 'COSMETICS_SHOP_POPUP_NONCE', 'cosmetics_shop_popup_dismiss' );
define( 'COSMETICS_SHOP_POPUP_SNOOZE', 2 * DAY_IN_SECONDS );
define( 'COSMETICS_SHOP_PRO_SLUG', 'cosmetics-shop-wordpress-theme' );
define( 'COSMETICS_SHOP_PRO_PRICE_URL', 'https://www.vwthemes.com/products/' . COSMETICS_SHOP_PRO_SLUG . '.js' );
define( 'COSMETICS_SHOP_PRO_PRICE_TRANSIENT', 'cosmetics_shop_pro_price' );

/**
 * Fetch the premium theme price from the store by product slug.
 *
 * Cached for 12 hours (1 hour on failure). Returns a formatted price
 * such as "$60", or an empty string if the price could not be fetched.
 */
function cosmetics_shop_get_pro_price() {
	$cosmetics_shop_price = get_transient( COSMETICS_SHOP_PRO_PRICE_TRANSIENT );

	if ( false !== $cosmetics_shop_price ) {
		return $cosmetics_shop_price;
	}

	$cosmetics_shop_price    = '';
	$cosmetics_shop_response = wp_remote_get( COSMETICS_SHOP_PRO_PRICE_URL, array( 'timeout' => 10 ) );

	if ( ! is_wp_error( $cosmetics_shop_response ) && 200 === wp_remote_retrieve_response_code( $cosmetics_shop_response ) ) {
		$cosmetics_shop_product = json_decode( wp_remote_retrieve_body( $cosmetics_shop_response ) );

		// Store returns price in cents.
		if ( isset( $cosmetics_shop_product->price ) && is_numeric( $cosmetics_shop_product->price ) ) {
			$cosmetics_shop_amount = $cosmetics_shop_product->price / 100;
			$cosmetics_shop_price  = '$' . number_format_i18n( $cosmetics_shop_amount, floor( $cosmetics_shop_amount ) == $cosmetics_shop_amount ? 0 : 2 );
		}
	}

	set_transient( COSMETICS_SHOP_PRO_PRICE_TRANSIENT, $cosmetics_shop_price, '' === $cosmetics_shop_price ? HOUR_IN_SECONDS : 12 * HOUR_IN_SECONDS );

	return $cosmetics_shop_price;
}

/**
 * Whether the popup should render for the current request.
 */
function cosmetics_shop_popup_should_show() {
	if ( is_admin() || ! is_user_logged_in() || ! is_super_admin() ) {
		return false;
	}

	// A child theme that ships its own popup takes over.
	if ( is_child_theme() && file_exists( get_stylesheet_directory() . '/inc/frontend-popup.php' ) ) {
		return false;
	}

	$cosmetics_shop_dismissed = (int) get_user_meta( get_current_user_id(), COSMETICS_SHOP_POPUP_META_KEY, true );

	return ( time() - $cosmetics_shop_dismissed ) >= COSMETICS_SHOP_POPUP_SNOOZE;
}

/**
 * Render popup in the front-end footer.
 */
function cosmetics_shop_frontend_promo_popup() {

	if ( ! cosmetics_shop_popup_should_show() ) {
		return;
	}

	$cosmetics_shop_logo_url = get_template_directory_uri() . '/images/final-logo.png';
	$cosmetics_shop_buy_url  = defined( 'COSMETICS_SHOP_BUY_NOW' ) ? COSMETICS_SHOP_BUY_NOW : 'https://www.vwthemes.com/products/cosmetics-shop-wordpress-theme';
	$cosmetics_shop_demo_url = defined( 'COSMETICS_SHOP_LIVE_DEMO' ) ? COSMETICS_SHOP_LIVE_DEMO : 'https://www.vwthemes.net/cosmetics-shop-pro/';
	$cosmetics_shop_nonce    = wp_create_nonce( COSMETICS_SHOP_POPUP_NONCE );
	$cosmetics_shop_price    = cosmetics_shop_get_pro_price();
	?>
	<div class="vw-upsell-popup" role="dialog" aria-labelledby="vw-upsell-popup-title" data-nonce="<?php echo esc_attr( $cosmetics_shop_nonce ); ?>">
		<div class="vw-upsell-popup__inner">
			<img src="<?php echo esc_url( $cosmetics_shop_logo_url ); ?>" alt="<?php esc_attr_e( 'VW Themes', 'cosmetics-shop' ); ?>" class="vw-upsell-popup__logo" />
			<h2 id="vw-upsell-popup-title" class="vw-upsell-popup__title"><?php esc_html_e( 'Take Your Website Further with Cosmetics Shop Pro', 'cosmetics-shop' ); ?></h2>
			<p class="vw-upsell-popup__text"><?php
				if ( $cosmetics_shop_price ) {
					printf(
						/* translators: %s: Pro theme price. */
						esc_html__( 'Upgrade to Pro & get access to premium features, extra sections and Customizations just at %s', 'cosmetics-shop' ),
						'<strong>' . esc_html( $cosmetics_shop_price ) . '</strong>'
					);
				} else {
					esc_html_e( 'Upgrade to Pro & get access to premium features, extra sections and Customizations', 'cosmetics-shop' );
				}
				?>
			</p>
			<div class="vw-upsell-popup__actions">
				<a href="<?php echo esc_url( $cosmetics_shop_buy_url ); ?>" class="vw-upsell-popup__btn vw-upsell-popup__btn--buy" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get Pro Now', 'cosmetics-shop' ); ?></a>
				<?php if ( $cosmetics_shop_demo_url ) : ?>
					<a href="<?php echo esc_url( $cosmetics_shop_demo_url ); ?>" class="vw-upsell-popup__btn vw-upsell-popup__btn--demo" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Live Demo', 'cosmetics-shop' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<button type="button" class="vw-upsell-popup__close" aria-label="<?php esc_attr_e( 'Close', 'cosmetics-shop' ); ?>">
			<span aria-hidden="true">&times;</span>
		</button>
	</div>
	<?php
}
add_action( 'wp_footer', 'cosmetics_shop_frontend_promo_popup' );

/**
 * Enqueue popup CSS + JS on the front end.
 */
function cosmetics_shop_frontend_promo_popup_assets() {

	if ( ! cosmetics_shop_popup_should_show() ) {
		return;
	}

	$cosmetics_shop_version = wp_get_theme()->get( 'Version' );

	wp_enqueue_style(
		'cosmetics-shop-upsell-popup',
		get_template_directory_uri() . '/css/frontend-popup.css',
		array(),
		$cosmetics_shop_version
	);

	wp_enqueue_script(
		'cosmetics-shop-upsell-popup',
		get_template_directory_uri() . '/js/frontend-popup.js',
		array(),
		$cosmetics_shop_version,
		true
	);

	wp_localize_script(
		'cosmetics-shop-upsell-popup',
		'CosmeticsShopPopup',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'action'  => 'cosmetics_shop_dismiss_popup',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'cosmetics_shop_frontend_promo_popup_assets' );

/**
 * AJAX handler: snooze the popup for the current user.
 */
function cosmetics_shop_dismiss_popup_handler() {

	if ( ! is_super_admin() ) {
		wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
	}

	check_ajax_referer( COSMETICS_SHOP_POPUP_NONCE, 'nonce' );

	update_user_meta( get_current_user_id(), COSMETICS_SHOP_POPUP_META_KEY, time() );

	wp_send_json_success();
}
add_action( 'wp_ajax_cosmetics_shop_dismiss_popup', 'cosmetics_shop_dismiss_popup_handler' );

/**
 * Reset the snooze for all users so the popup shows again
 * whenever this theme is activated (fresh install or re-activation).
 */
function cosmetics_shop_reset_popup_dismissal() {
	delete_metadata( 'user', 0, COSMETICS_SHOP_POPUP_META_KEY, '', true );
}
add_action( 'after_switch_theme', 'cosmetics_shop_reset_popup_dismissal' );

/**
 * Also clear it when switching away from this theme (deactivation).
 */
add_action( 'switch_theme', 'cosmetics_shop_reset_popup_dismissal' );
