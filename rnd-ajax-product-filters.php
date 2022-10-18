<?php
/**
 * Plugin Name:    RND Product Filters with ajax for WooCommerce
 * Plugin URI:     https://rndexperts.com
 * Description:    A plugin to filter woocommerce products with product categories, attributes, prices and reviews with ajax on product page and category page.
 * Author:         RND Experts, Rajeev Kumar
 * Author URI:     https://rndexperts.com
 * Text Domain:    rndapf
 * Domain Path:    /languages
 * Version:         1.1
 *
 * @package         Rnd_Ajax_Product_Filters
 */

// Your code starts here.
class Rnd_Ajax_Product_Filters
{
    /**
     *
     *
     * @since 1.0
     * @var string
     */
    public $version = '1.1';

    /*
     * construct function
     *
     * @since 1.0
     */
    public function __construct()
    { 
        $this->rnd_register_widget_includes();
    }

    public function rnd_register_widget_includes()
    { 
        include untrailingslashit(plugin_dir_path(__FILE__)). '/ajax/rndapf.php';
    }
   
}
new Rnd_Ajax_Product_Filters();
