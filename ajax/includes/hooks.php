<?php
/**
 * List of hooks in WC Ajax Product Filter plugin.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

$rndapf = new RNDAPF();

add_action('woocommerce_before_shop_loop', array('RNDAPF', 'beforeProductsHolder'), 0);
add_action('woocommerce_after_shop_loop', array('RNDAPF', 'afterProductsHolder'), 200);
remove_action('woocommerce_after_shop_loop', 'woocommerce_pagination', 10);
add_action('woocommerce_after_shop_loop', array('RNDAPF','rnd_woocommerce_products_load_more'), 9);
add_action('wp_ajax_filter_products', array($rndapf,'render'),20); 
add_action('wp_ajax_nopriv_filter_products', array($rndapf,'render'),20); 

add_action('paginate_links', array('RNDAPF', 'paginateLinks'));

// frontend sctipts
add_action('wp_enqueue_scripts', array($rndapf, 'frontendScripts'));

// filter products
//add_action('woocommerce_product_query', array($rndapf, 'setFilter'));

// clear old transients
add_action('create_term', 'rndapf_clear_transients');
add_action('edit_term', 'rndapf_clear_transients');
add_action('delete_term', 'rndapf_clear_transients');

add_action('save_post', 'rndapf_clear_transients');
add_action('delete_post', 'rndapf_clear_transients');