<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * rndapf main class
 */
if (!class_exists('RNDAPF')) {
    class RNDAPF
    {
        /**
         * Plugin version, used for cache-busting of style and script file references.
         *
         * @var string
         */
        public $version = '1.1';

        /**
         * Unique identifier for the plugin.
         *
         * The variable name is used as the text domain when internationalizing strings of text.
         *
         * @var string
         */
        public $plugin_slug;

        /**
         * A reference to an instance of this class.
         *
         * @var RNDAPF
         */
        private static $_instance = null;

        /**
         * Initialize the plugin.
         */
        public function __construct()
        {
            add_action('plugins_loaded', array($this, 'init'));
            // add_action('init', array($this,'rnd_load_more_init' ));
        }
        

        /**
         * Returns an instance of this class.
         *
         * @return rndapf
         */
        public static function instance()
        {
            if (!isset(self::$_instance)) {
                self::$_instance = new rndapf();
            }

            return self::$_instance;
        }

        /**
         * Init this plugin when WordPress Initializes.
         */
        public function init()
        {
            $this->plugin_slug = '';

            // Grab the translation for the plugin.
            add_action('init', array($this, 'loadPluginTextdomain'));

            // If woocommerce class exists and woocommerce version is greater than required version.
            if (class_exists('woocommerce') && WC()->version >= 2.1) {
                $this->defineConstants();
                $this->includes();

                // plugin action links
                add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'pluginActionLinks'));

                // plugin settings page
                if (is_admin()) {
                    $this->pluginSettingsPageInit();
                }
            }
            // If woocommerce class exists but woocommerce version is older than required version.
            elseif (class_exists('woocommerce')) {
                add_action('admin_notices', array($this, 'updateWoocommerce'));
            }
            // If woocommerce plugin not found.
            else {
                add_action('admin_notices', array($this, 'needWoocommerce'));
            }
        }

        /**
         * Defind constants for this plugin.
         */
        public function defineConstants()
        {
            $this->define('RNDAPF_LOCALE', $this->plugin_slug);
            $this->define('RNDAPF_PATH', $this->pluginPath());
            $this->define('RNDAPF_ASSETS_PATH', $this->assetsPath());
            $this->define('RNDAPF_CACHE_TIME', 60*60*12);
        }

        /**
         * Include required core files.
         */
        public function includes()
        {
            require_once 'includes/functions.php';
            require_once 'includes/hooks.php';
            require_once 'widgets/widget-category-filter.php';
			
        }

        /**
         * Define constants if not already defined.
         *
         * @param  string $name
         * @param  string|bool $value
         */
        public function define($name, $value)
        {
            if (!defined($name)) {
                define($name, $value);
            }
        }

        /**
         * Register and enqueue frontend scripts.
         *
         * @return mixed
         */
        public function frontendScripts()
        {
            wp_register_style('rndapf-style', RNDAPF_ASSETS_PATH . 'css/rndapf-styles.css');
            wp_register_style('font-awesome', RNDAPF_ASSETS_PATH . 'css/font-awesome.min.css');
            wp_register_script('rndapf-script', RNDAPF_ASSETS_PATH . 'js/scripts.js', array('jquery'), '20120206', true);
            wp_localize_script('rndapf-script', 'rndapf_price_filter_params', array(
                'currency_symbol' => get_woocommerce_currency_symbol(),
                'currency_pos'    => get_option('woocommerce_currency_pos'),
                'ajaxurl'         => admin_url('admin-ajax.php'),

            ));
            if ($settings = get_option('rndapf_settings')) {
                wp_localize_script('rndapf-script', 'rndapf_params', $settings);
            }
            wp_register_style('rndapf-nouislider-style', RNDAPF_ASSETS_PATH . 'css/nouislider.min.css');
            wp_register_script('rndapf-nouislider-script', RNDAPF_ASSETS_PATH . 'js/nouislider.min.js', array('jquery'), '1.0', true);
            wp_register_script('rndapf-price-filter-script', RNDAPF_ASSETS_PATH . 'js/price-filter.js', array('jquery'), '1.0', true);
            wp_register_script('rndapf-reviews-filter-script', RNDAPF_ASSETS_PATH . 'js/reviews-filter.js', array('jquery'), '1.0', true);

            wp_register_style('rndapf-select2', RNDAPF_ASSETS_PATH . 'css/select2.css');
            wp_register_script('rndapf-select2', RNDAPF_ASSETS_PATH . 'js/select2.min.js', array('jquery'), '1.0', true);
        }

        /**
         * Get plugin settings.
         */
        public function pluginSettingsPageInit()
        {
            add_action('admin_menu', array($this, 'adminMenu'));
            add_action('admin_init', array($this, 'registerSettings'));
            add_action('admin_init', array($this, 'saveDefultSettings'));
        }

        /**
         * Register admin menu.
         */
        public function adminMenu()
        {
            add_options_page(__('RND Ajax Product Filter', $this->plugin_slug), __('RND Ajax Product Filter', $this->plugin_slug), 'manage_options', 'rndapf-settings', array($this, 'settingsPage'));
        }

        /**
         * Render HTML for settings page.
         */
        public function settingsPage()
        {
            require 'includes/settings.php';
        }

        /**
         * Default settings for this plugin.
         *
         * @return array
         */
        public function defaultSettings()
        {
            return array(
                'shop_loop_container'  => '.rndapf-before-products',
                'not_found_container'  => '.rndapf-before-products',
                'pagination_container' => '.woocommerce-pagination',
                'overlay_bg_color'     => '#fff',
                'sorting_control'      => '1',
                'scroll_to_top'        => '1',
                'scroll_to_top_offset' => '100',
                'custom_scripts'       => ''
            );
        }

        /**
         * Register settings.
         */
        public function registerSettings()
        {
            register_setting('rndapf_settings', 'rndapf_settings', array($this, 'validateSettings'));
        }

        /**
         * If settings are not found then save it.
         */
        public function saveDefultSettings()
        {
            if (!get_option('rndapf_settings')) {
                // check if filter is applied
                $settings = apply_filters('rndapf_settings', $this->defaultSettings());
                update_option('rndapf_settings', $settings);
            }
        }

        /**
         * Check for if filter is applied.
         *
         * @param  array $input
         * @return array
         */
        public function validateSettings($input)
        {
            if (has_filter('rndapf_settings')) {
                $input = apply_filters('rndapf_settings', $input);
            }

            return $input;
        }

        /**
         * Get chosen filters.
         *
         * @return array
         */
        public function getChosenFilters()
        {
            // parse url
            if (isset($_POST['query'])) {
                $url = sanitize_url($_POST['query']);
                $url_components = parse_url($url);
                parse_str($url_components['query'], $query);
            } else {
                $url = $_SERVER['QUERY_STRING'];
                parse_str($url, $query);
            }
            

            $chosen = array();
            $term_ancestors = array();
            $active_filters = array();

            // keyword
            if (isset($_POST['keyword'])) {
                $keyword = (!empty($_POST['keyword'])) ? sanitize_text_field($_POST['keyword']) : '';
                $active_filters['keyword'] = $keyword;
            }

            // orderby
            if (isset($_POST['orderby'])) {
                $orderby = (!empty($_POST['orderby'])) ? sanitize_text_field($_POST['orderby']) : '';
                $active_filters['orderby'] = $orderby;
            }

            foreach ($query as $key => $value) {
                // attribute
                if (preg_match('/^attr/', $key) && !empty($query[$key])) {
                    $terms = explode(',', $value);
                    $new_key = str_replace(array('attra-', 'attro-'), '', $key);
                    $taxonomy = 'pa_' . $new_key;

                    if (preg_match('/^attra/', $key)) {
                        $query_type = 'and';
                    } else {
                        $query_type = 'or';
                    }

                    $chosen[$taxonomy] = array(
                        'terms'      => $terms,
                        'query_type' => $query_type
                    );

                    foreach ($terms as $term_id) {
                        $ancestors = rndapf_get_term_ancestors($term_id, $taxonomy);
                        $term_data = rndapf_get_term_data($term_id, $taxonomy);
                        $term_ancestors[$key][] = $ancestors;
                        $active_filters['term'][$key][$term_id] = $term_data->name;
                    }
                }

                // category
                if (preg_match('/product-cat/', $key) && !empty($query[$key])) {
                    $terms = explode(',', $value);
                    $taxonomy = 'product_cat';

                    if (preg_match('/^product-cata/', $key)) {
                        $query_type = 'and';
                    } else {
                        $query_type = 'or';
                    }
                    $chosen[$taxonomy] = array(
                        'terms'      => $terms,
                        'query_type' => $query_type
                    );

                    foreach ($terms as $term_id) {
                        $ancestors = rndapf_get_term_ancestors($term_id, $taxonomy);
                        $term_data = rndapf_get_term_data($term_id, $taxonomy);
                        $term_ancestors[$key][] = $ancestors;
                        $active_filters['term'][$key][$term_id] = $term_data->name;
                    }
                }
            }
            // min-price
            if (isset($_POST['min-price'])) {
                $active_filters['min_price'] = sanitize_text_field($_POST['min-price']);
            }

            // max-price
            if (isset($_POST['max-price'])) {
                $active_filters['max_price'] = sanitize_text_field($_POST['max-price']);
            }
            // min-price
            if (isset($_POST['min-rating'])) {
                $active_filters['min-rating'] = sanitize_text_field($_POST['min-rating']);
            }

            // max-price
            if (isset($_POST['max-rating'])) {
                $active_filters['max-rating'] = sanitize_text_field($_POST['max-rating']);
            }

            return array(
                'chosen'         => $chosen,
                'term_ancestors' => $term_ancestors,
                'active_filters' => $active_filters
            );
        }

        /**
         * Filtered product ids for given terms.
         *
         * @return array
         */
        public function filteredProductIdsForTerms()
        {
            $chosen_filters = $this->getChosenFilters();
            $chosen_filters = $chosen_filters['chosen'];
            $results = array();

            // 99% copy of WC_Query
            if (sizeof($chosen_filters) > 0) {
                $matched_products = array(
                    'and' => array(),
                    'or'  => array()
                );

                $filtered_attribute = array(
                    'and' => false,
                    'or'  => false
                );

                foreach ($chosen_filters as $attribute => $data) {
                    $matched_products_from_attribute = array();
                    $filtered = false;

                    if (sizeof($data['terms']) > 0) {
                        foreach ($data['terms'] as $value) {
                            $posts = get_posts(
                                array(
                                    'post_type'     => 'product',
                                    'numberposts'   => -1,
                                    'post_status'   => 'publish',
                                    'fields'        => 'ids',
                                    'no_found_rows' => true,
                                    'tax_query'     => array(
                                        array(
                                            'taxonomy' => $attribute,
                                            'terms'    => $value,
                                            'field'    => 'term_id'
                                        )
                                    )
                                )
                            );

                            if (!is_wp_error($posts)) {
                                if (sizeof($matched_products_from_attribute) > 0 || $filtered) {
                                    $matched_products_from_attribute = ($data['query_type'] === 'or') ? array_merge($posts, $matched_products_from_attribute) : array_intersect($posts, $matched_products_from_attribute);
                                } else {
                                    $matched_products_from_attribute = $posts;
                                }

                                $filtered = true;
                            }
                        }
                    }

                    if (sizeof($matched_products[$data['query_type']]) > 0 || $filtered_attribute[$data['query_type']] === true) {
                        $matched_products[$data['query_type']] = ($data['query_type'] === 'or') ? array_merge($matched_products_from_attribute, $matched_products[$data['query_type']]) : array_intersect($matched_products_from_attribute, $matched_products[$data['query_type']]);
                    } else {
                        $matched_products[$data['query_type']] = $matched_products_from_attribute;
                    }

                    $filtered_attribute[$data['query_type']] = true;
                }

                // combine our AND and OR result sets
                if ($filtered_attribute['and'] && $filtered_attribute['or']) {
                    $results = array_intersect($matched_products['and'], $matched_products['or']);
                    $results[] = 0;
                } else {
                    $results = array_merge($matched_products['and'], $matched_products['or']);
                    $results[] = 0;
                }
            }

            return $results;
        }

        /**
         * Query for meta that should be set to the main query.
         *
         * @return array
         */
        public function queryForMeta()
        {
            $meta_query = array();

            // rating filter
            if (isset($_POST['min_rating'])) {
                $meta_query[] = array(
                    'key'           => '_wc_average_rating',
                    'value'         => isset($_POST['min_rating']) ? floatval($_POST['min_rating']) : 0,
                    'compare'       => '>=',
                    'type'          => 'DECIMAL',
                    'rating_filter' => true,
                );
            }

            // price range for all published products
            $unfiltered_price_range = $this->getPriceRange(false);

            if (isset($_POST['min-price']) || isset($_POST['max-price'])) {
                if (sizeof($unfiltered_price_range) === 2) {
                    $min = (!empty($_POST['min-price'])) ? (int)$_POST['min-price'] : '';
                    $max = (!empty($_POST['max-price'])) ? (int)$_POST['max-price'] : '';

                    $min = (!empty($min)) ? $min : (int)$unfiltered_price_range[0];
                    $max = (!empty($max)) ? $max : (int)$unfiltered_price_range[1];

                    // if tax enabled
                    if (wc_tax_enabled() && 'incl' === get_option('woocommerce_tax_display_shop') && ! wc_prices_include_tax()) {
                        $tax_classes = array_merge(array( ''), WC_Tax::get_tax_classes());

                        foreach ($tax_classes as $tax_class) {
                            $tax_rates = WC_Tax::get_rates($tax_class);
                            $class_min = $min - WC_Tax::get_tax_total(WC_Tax::calc_exclusive_tax($min, $tax_rates));
                            $class_max = $max - WC_Tax::get_tax_total(WC_Tax::calc_exclusive_tax($max, $tax_rates));

                            $min = $max = false;

                            if ($min === false || $min > (int)$class_min) {
                                $min = floor($class_min);
                            }

                            if ($max === false || $max < (int)$class_max) {
                                $max = ceil($class_max);
                            }
                        }
                    }

                    // if WooCommerce Currency Switcher plugin is activated
                    if (class_exists('WOOCS')) {
                        $woocs = new WOOCS();
                        $chosen_currency = $woocs->get_woocommerce_currency();
                        $currencies = $woocs->get_currencies();

                        if (sizeof($currencies) > 0) {
                            foreach ($currencies as $currency) {
                                if ($currency['name'] == $chosen_currency) {
                                    $rate = $currency['rate'];
                                }
                            }

                            $min = floor($min / $rate);
                            $max = ceil($max / $rate);
                        }
                    }

                    $meta_query[] = array(
                        'key'          => '_price',
                        'value'        => array($min, $max),
                        'type'         => 'numeric',
                        'compare'      => 'BETWEEN',
                        'price_filter' => true,
                    );
                }
            }

            return $meta_query;
        }

        /**
         * Set filter.
         *
         * @param wp_query $q
         */
        public function setFilter($q)
        {
            // check for if we are on main query and product archive page
            if (!is_main_query() && !is_post_type_archive('product') && !is_tax(get_object_taxonomies('product'))) {
                return;
            }

            $search_results = $this->productIdsForGivenKeyword();
            $taxono_results = $this->filteredProductIdsForTerms();
            if (sizeof($search_results) > 0 && sizeof($taxono_results) > 0) {
                $post__in = array_intersect($search_results, $taxono_results);
            } elseif (sizeof($search_results) > 0 && sizeof($taxono_results) === 0) {
                $post__in = $search_results;
            } else {
                $post__in = $taxono_results;
            }

            $q->set('meta_query', $this->queryForMeta());
            $q->set('post__in', $post__in);

            return;
        }

        /**
         * Retrive Product ids for given keyword.
         *
         * @return array
         */
        public function productIdsForGivenKeyword()
        {
            if (isset($_POST['keyword']) && !empty($_POST['keyword'])) {
                $keyword = sanitize_text_field($_POST['keyword']);
                
                $args = array(
                    's'           => $keyword,
                    'post_type'   => 'product',
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    'fields'      => 'ids'
                );

                $results = get_posts($args);
                $results[] = 0;
            } else {
                $results = array();
            }

            return $results;
        }

        /**
         * Get the unfiltered product ids.
         *
         * @return array
         */
        public function unfilteredProductIds()
        {
            $args = array(
                'post_type'   => 'product',
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields'      => 'ids'
            );

            // get unfiltered products using transients
            $transient_name = 'rndapf_unfiltered_product_ids';

            if (false === ($unfiltered_product_ids = get_transient($transient_name))) {
                $unfiltered_product_ids = get_posts($args);
                set_transient($transient_name, $unfiltered_product_ids, rndapf_CACHE_TIME);
            }

            return $unfiltered_product_ids;
        }

        /**
         * Get filtered product ids.
         *
         * @return array
         */
        public function filteredProductIds()
        {
            global $wp_query;
            $current_query = $wp_query;
            
            if (!is_object($current_query) && !is_main_query() && !is_post_type_archive('product') && !is_tax(get_object_taxonomies('product'))) {
                return;
            }
            $modified_query = $current_query->query;
            unset($modified_query['paged']);
            $meta_query = (key_exists('meta_query', $current_query->query_vars)) ? $current_query->query_vars['meta_query'] : array();
            $tax_query = (key_exists('tax_query', $current_query->query_vars)) ? $current_query->query_vars['tax_query'] : array();
            $post__in = (key_exists('post__in', $current_query->query_vars)) ? $current_query->query_vars['post__in'] : array();
            
            $filtered_product_ids = get_posts(
                array_merge(
                    $modified_query,
                    array(
                        'post_type'   => 'product',
                        'numberposts' => -1,
                        'post_status' => 'publish',
                        'post__in'    => $post__in,
                        'meta_query'  => $meta_query,
                        'tax_query'   => $tax_query,
                        'fields'      => 'ids',
                        'no_found_rows' => true,
                        'update_post_meta_cache' => false,
                        'update_post_term_cache' => false,
                        'pagename'    => '',
                    )
                )
            );

            return $filtered_product_ids;
        }

        /**
         * Find Prices for given products.
         *
         * @param  array $products
         * @return array
         */
        public function findPriceRange($products)
        {
            $price_range = array();

            foreach ($products as $id) {
                $meta_value = get_post_meta($id, '_price', true);
                
                if ($meta_value) {
                    $price_range[] = $meta_value;
                }

                // for child posts
                $product_variation = get_children(
                    array(
                        'post_type'   => 'product_variation',
                        'post_parent' => $id,
                        'numberposts' => -1
                    )
                );

                if (sizeof($product_variation) > 0) {
                    foreach ($product_variation as $variation) {
                        $meta_value = get_post_meta($variation->ID, '_price', true);
                        if ($meta_value) {
                            $price_range[] = $meta_value;
                        }
                    }
                }
            }

            $price_range = array_unique($price_range);

            return $price_range;
        }

        /**
         * Find price range for filtered products.
         *
         * @return array
         */
        public function filteredProductsPriceRange()
        {
            $products = $this->filteredProductIds();

            if (sizeof($products) < 1) {
                return;
            }

            $filtered_products_price_range = $this->findPriceRange($products);

            return $filtered_products_price_range;
        }

        /**
         * Find price range for unfiltered products.
         *
         * @return array
         */
        public function unfilteredProductsPriceRange()
        {
            $products = $this->unfilteredProductIds();

            if (sizeof($products) < 1) {
                return;
            }

            // get unfiltered products price range using transients
            $transient_name = 'rndapf_unfiltered_product_price_range';

            if (false === ($unfiltered_products_price_range = get_transient($transient_name))) {
                $unfiltered_products_price_range = $this->findPriceRange($products);
                set_transient($transient_name, $unfiltered_products_price_range, rndapf_CACHE_TIME);
            }

            return $unfiltered_products_price_range;
        }

        /**
         * Get Price Range for given product ids.
         * If filtered is true then return price range for filtered products,
         * otherwise return price range for all products.
         *
         * @param  boolean $filtered
         * @return array
         */
        public function getPriceRange($filtered = true)
        {
            if ($filtered === true) {
                $price_range = $this->filteredProductsPriceRange();
            } else {
                $price_range = $this->unfilteredProductsPriceRange();
            }

            if (sizeof($price_range) > 2) {
                $min = $max = false;

                foreach ($price_range as $price) {
                    if ($min === false || $min > (int)$price) {
                        $min = floor($price);
                    }
                    
                    if ($max === false || $max < (int)$price) {
                        $max = ceil($price);
                    }
                }
                // if tax enabled and shop page shows price including tax
                if (wc_tax_enabled() && 'incl' === get_option('woocommerce_tax_display_shop') && ! wc_prices_include_tax()) {
                    $tax_classes = array_merge(array( ''), WC_Tax::get_tax_classes());

                    foreach ($tax_classes as $tax_class) {
                        $tax_rates = WC_Tax::get_rates($tax_class);
                        $class_min = $min + WC_Tax::get_tax_total(WC_Tax::calc_exclusive_tax($min, $tax_rates));
                        $class_max = $max + WC_Tax::get_tax_total(WC_Tax::calc_exclusive_tax($max, $tax_rates));

                        $min = $max = false;

                        if ($min === false || $min > (int)$class_min) {
                            $min = floor($class_min);
                        }

                        if ($max === false || $max < (int)$class_max) {
                            $max = ceil($class_max);
                        }
                    }
                }
                // if WooCommerce Currency Switcher plugin is activated
                if (class_exists('WOOCS')) {
                    $woocs = new WOOCS();
                    $chosen_currency = $woocs->get_woocommerce_currency();
                    $currencies = $woocs->get_currencies();

                    if (sizeof($currencies) > 0) {
                        foreach ($currencies as $currency) {
                            if ($currency['name'] == $chosen_currency) {
                                $rate = $currency['rate'];
                            }
                        }
                        $min = floor($min * $rate);
                        $max = ceil($max * $rate);
                    }
                }

                if ($min == $max) {
                    // empty array
                    return array();
                } else {
                    // array with min and max values
                    return array($min, $max);
                }
            } else {
                // empty array
                return array();
            }
        }

        /**
         * HTML wrapper to insert before the shop loop.
         *
         * @return string
         */
        public static function beforeProductsHolder()
        {
            global $wp_query;
            $category  ='';
            $category = $wp_query->get_queried_object();
            if (is_object($category) && isset($category->term_id) ) {
                $term_id = $category->term_id?:'';
                $slug =  $category->slug?:'';
                $term_link = $category->term_id?get_term_link($category->term_id):'';

                echo '<div class="rndapf-before-products" data-category_id="'.esc_attr($term_id).'" data-category="'. esc_attr($slug) .'" data-permalink="'. esc_attr($term_link) .'">';
            } else {
                echo '<div class="rndapf-before-products" data-category_id="" data-category="" data-permalink="">';
            }
        }

        /**
         * HTML wrapper to insert after the shop loop.
         *
         * @return string
         */
        public static function afterProductsHolder()
        {
            echo '</div>';
        }

        /**
         * HTML wrapper to insert before the not found product loops.
         *
         * @param  string $template_name
         * @param  string $template_path
         * @param  string $located
         * @return string
         */
        public static function beforeNoProducts($template_name = '', $template_path = '', $located = '')
        {
            if ($template_name == 'loop/no-products-found.php') {
                echo '<div class="rndapf-before-products">';
            }
        }

        /**
         * HTML wrapper to insert after the not found product loops.
         *
         * @param  string $template_name
         * @param  string $template_path
         * @param  string $located
         * @return string
         */
        public static function afterNoProducts($template_name = '', $template_path = '', $located = '')
        {
            if ($template_name == 'loop/no-products-found.php') {
                echo '</div>';
            }
        }

        /**
         * Decode pagination links.
         *
         * @param string $link
         *
         * @return string
         */
        public static function paginateLinks($link)
        {
            $link = urldecode($link);
            return $link;
        }

        /**
         * Load the plugin text domain for translation.
         */
        public function loadPluginTextdomain()
        {
            load_plugin_textdomain('rndapf', false, basename(dirname(__FILE__)) . '/languages/');
        }

        /**
         * Get the plugin Path.
         *
         * @return string
         */
        public function pluginPath()
        {
            return untrailingslashit(plugin_dir_url(__FILE__));
        }

        /**
         * Get the plugin assets path.
         *
         * @return string
         */
        public function assetsPath()
        {
            return trailingslashit(plugin_dir_url(__FILE__) . 'assets/');
        }

        /**
         * Show admin notice if woocommerce plugin not found.
         */
        public function needWoocommerce()
        {
            echo '<div class="error">';
            echo '<p>' . __('RND Ajax Product Filter needs WooCommerce plguin to work.', $this->plugin_slug) . '</p>';
            echo '</div>';
        }

        /**
         * Show admin notice if woocommerce plugin version is older than required version.
         */
        public function updateWoocommerce()
        {
            echo '<div class="error">';
            echo '<p>' . __('To use RND Ajax Product Filter update your WooCommerce plugin.', $this->plugin_slug) . '</p>';
            echo '</div>';
        }

        /**
         * Show action links on the plugins page.
         *
         * @param  array $links
         * @return array
         */
        public function pluginActionLinks($links)
        {
            $links[] = '<a href="' . admin_url('options-general.php?page=rndapf-settings') . '">' . __('Settings', $this->plugin_slug) . '</a>';
            return $links;
        }
  /**
     * Widget ajax listener
     */
    public function render()
    {
        global $wp_query, $wp_rewrite;
        $args= [];
        $ordering_args =[];
        $pagination_args =[];
        $taxonomy   = 'product_cat';
        $search_results = $this->productIdsForGivenKeyword();
        $taxono_results = $this->filteredProductIdsForTerms();
        $meta_query_results = $this->queryForMeta();
        $args = array(
            'post_type'           => 'product',
            'post_status'         => 'publish',
            'posts_per_page'      =>  absint(get_option('posts_per_page')),
        );

        if (isset($_POST['orderby'])) {
            if ('price-desc' === $_POST['orderby']) {
                $args['orderby'] = 'price';
                $args['order']   = 'DESC';
            } elseif ('price' === $_POST['orderby']) {
                $args['orderby'] = 'price';
                $args['order']   = 'ASC';
            } elseif ('date' === $_POST['orderby']) {
                $args['orderby'] = 'date';
                $args['order']   = 'DESC';
            } else {
                $args['orderby'] = sanitize_text_field($_POST['orderby']);
                $args['order'] ='';

            }

            $ordering_args = WC()->query->get_catalog_ordering_args($args['orderby'], $args['order']);
            $args['orderby'] = $ordering_args['orderby'];
            $args['ordering_args']   = $ordering_args;
            $args['order']   = $ordering_args['order'];
            if ($ordering_args['meta_key']) {
                $args['meta_key'] = $ordering_args['meta_key'];
            }
        } else {
            $ordering_args = WC()->query->get_catalog_ordering_args();
            $args['orderby'] = $ordering_args['orderby'];
            $args['ordering_args']   = $ordering_args;
            $args['order']   = $ordering_args['order'];
            if ($ordering_args['meta_key']) {
                $args['meta_key'] = $ordering_args['meta_key'];
            }
        }
        
        if (isset($_POST['paged'])) {
            $args['paged'] = absint($_POST['paged']);
            if (1 < absint($_POST['paged'])) {
                $args['paged'] = absint($_POST['paged']);
            }
            $pagination_args = array(
                'total'   => wc_get_loop_prop('total_pages'),
                'current' =>absint($_POST['paged']),
            );
            $args['limit'] = isset($args['posts_per_page']) ? intval($args['posts_per_page']) : intval(get_option('posts_per_page'));

            if (empty($args['offset'])) {
               $args['offset'] = 1 < $args['paged'] ? ($args['paged'] - 1) * $args['limit'] : 0;
            }
        }
        if (!empty($args['meta_query'])) {
            if (!empty($_POST['rating'])) {
                $args['meta_query']['relation'] ='AND';
               $rating = array_map( 'sanitize_text_field', $_POST['rating'] );
                $args['meta_query'][]= [
                    'key'     => '_wc_average_rating',
                    'value'   => array( (int)$rating[0],  (int)$rating[1] ),
                    'compare' => 'BETWEEN',
                    'type'    => 'numeric'
                ];
            }
        } else {
            if (!empty($_POST['rating'])) {
                
            // Any of the WordPress data sanitization functions can be used here
                $rating = array_map( 'sanitize_text_field', $_POST['rating'] );
                $args['meta_query']= array(
                array(
                    'key'     => '_wc_average_rating',
                    'value'   => array( (int)$rating[0],  (int)$rating[1] ),
                    'compare' => 'BETWEEN',
                    'type'    => 'numeric'
                )
            );
            }
        }
        
          /* if price filter is settled */
        if (!empty($_POST['pricing'])) {
            $pricing = array_map( 'sanitize_text_field', $_POST['pricing'] );
         
            $args['meta_query'][]= array(
                array(
                    'key'     => '_price',
                    'value'   => array( (int)$pricing[0],  (int)$pricing[1] ),
                    'compare' => 'BETWEEN',
                    'type'    => 'numeric'
                )
            );
        }

        /*Display only in stock*/
        $args['meta_query'][]= [
            'key' => '_stock_status',
            'value' => 'instock'
        ];
        /*Display only visible product*/
        $args['tax_query'][]= [
                'taxonomy'  => 'product_visibility',
                'terms'     => array( 'exclude-from-catalog' ),
                'field'     => 'name',
                'operator'  => 'NOT IN',
            ];

    
        if (!empty($_POST['product-cata'])) {
            $category = sanitize_text_field($_POST['product-cata']);
            $args['tax_query'][]= [
                'taxonomy'   =>  $taxonomy,
                'field'      => 'term_id',
                'terms'      =>  (int)$category
            ];
        } else {
            if (!empty($_POST['category'])) {
                $category = sanitize_text_field($_POST['category']);
                $args['tax_query'][]= [
                    'taxonomy'   =>  $taxonomy,
                    'field'      => 'term_id',
                    'terms'      =>  (int)$category
                ];
            }

        }

        $wp_query = new WP_Query($args);

        if ($wp_query->have_posts()) {
            ob_start();
           // do_action('woocommerce_before_shop_loop');

            woocommerce_product_loop_start();


            while ($wp_query->have_posts()) {
                $wp_query->the_post();
                /**
                 * Hook: woocommerce_shop_loop.
                 */
                do_action('woocommerce_shop_loop');
                wc_get_template_part('content', 'product');
            }

            woocommerce_product_loop_end();
          
           // do_action('woocommerce_after_shop_loop');
        
            wp_reset_postdata();
            $_RESPONSE['products'] = ob_get_contents();
            ob_end_clean();
            if (isset($_POST['paged'])) {
                $paged = (int) $_POST['paged'];
                $base = sanitize_url($_POST['link']).'page/%#%';
                $pagination_args = array(
                    'base'    =>  $base,
                    'total'   => (int)$wp_query->max_num_pages,
                    'per_page' => (int)get_option('posts_per_page'),
                    'current' => (int) max(1,  $paged),
                    'format' => '',
                );

                $result_count_args = array(
                    'base'    =>  $base,
                    'total'   => (int)$wp_query->found_posts,
                    'per_page' => (int)get_option('posts_per_page'),
                    'current' => (int) max(1,  $paged),
                    'format' => '',
                );
                $_RESPONSE['pagination'] =  $this->custom_woocommerce_pagination($pagination_args);
                $_RESPONSE['result_counts'] =  $this->custom_result_count($result_count_args);
            } else {
                $base = sanitize_url($_POST['link']).'page/%#%';
                $pagination_args = array(
                    'base'    =>  $base,
                    'total'   =>(int) $wp_query->max_num_pages,
                    'per_page' =>(int) get_option('posts_per_page'),
                    'current' => (int) max(1, 1),
                    'format' => '',
                );
                $result_count_args = array(
                    'base'    =>  $base,
                    'total'   => (int)$wp_query->found_posts,
                    'per_page' => (int)get_option('posts_per_page'),
                    'current' => (int) max(1, 1),
                    'format' => '',
                );
                $_RESPONSE['pagination'] =  $this->custom_woocommerce_pagination($pagination_args);
                $_RESPONSE['result_counts'] =  $this->custom_result_count($result_count_args);
            }
            $_RESPONSE['args'] = $args;
            $_RESPONSE['pagination_args'] = $pagination_args;
            $_RESPONSE['found'] = 1;
            $_RESPONSE['active_filter'] =  $this->custom_active_filter_html($_POST);
        } else {
            ob_start();
            do_action('woocommerce_no_products_found');
            $_RESPONSE['products'] = ob_get_contents();
            $_RESPONSE['pagination'] = '';
            $_RESPONSE['result_counts'] = '';
            $_RESPONSE['args'] = $args;
            $_RESPONSE['found'] = 0;
            $_RESPONSE['active_filter'] =  $this->custom_active_filter_html($_POST);
            ob_end_clean();
        }
        echo json_encode($_RESPONSE);

        die();
    }

        public function custom_woocommerce_pagination($pagination_args, $out='')
        {
            ob_start();
            if (!empty($pagination_args)) {
                wc_get_template( 'loop/pagination.php', $pagination_args );
            }
            $out .= ob_get_clean();
            return  $out;
        }

        public function custom_result_count($pagination_args, $out='')
        {
            ob_start();
            if (!empty($pagination_args)) {
                wc_get_template( 'loop/result-count.php', $pagination_args );
            }
            $out .= ob_get_clean();
            return  $out;
        }


        public function custom_active_filter_html($pagination_args, $out='')
        {
            global $rndapf;
            ob_start();
            if (!empty($pagination_args)) {
                $active_filters = $rndapf->getChosenFilters();
                $active_filters = $active_filters['active_filters'];
                include untrailingslashit(plugin_dir_path(__FILE__)). '/templates/active-filter.php';
            }
            $out .= ob_get_clean();
            return  $out;
        }

        public static function rnd_woocommerce_products_load_more()
        {
            global $wp_query;
            $settings = apply_filters('rndapf_settings', get_option('rndapf_settings'));
            if (isset($settings['filters_turn_off'])) {
                return;
            }
            if (isset($settings['show_load_more_btn'])) {
                echo '<div id="container_load_more">';
                if ($wp_query->max_num_pages > 1) {
                    echo '<div id="btn_loadmore" data-current_page="1"  class="button">LOAD MORE</div>';
                }
                echo '</div>';
            }
        }
    }
}

/**
 * Instantiate this class globally.
 */
$GLOBALS['rndapf'] = RNDAPF::instance();
