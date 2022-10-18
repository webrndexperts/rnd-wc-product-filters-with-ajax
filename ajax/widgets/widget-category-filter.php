<?php
/**
 * WC Ajax Product Filter by Category
 */
if (!class_exists('RNDAPF_Category_Filter_Widget')) {
	class RNDAPF_Category_Filter_Widget extends WP_Widget {
		/**
		 * Register widget with WordPress.
		 */
		function __construct() {
			parent::__construct(
				'rndapf-category-filter', // Base ID
				__('RND Ajax Product Filter by Category', 'rndapf'), // Name
				array('description' => __('Filter woocommerce products by category.', 'rndapf')) // Args
			);
		}

		/**
		 * Front-end display of widget.
		 *
		 * @see WP_Widget::widget()
		 *
		 * @param array $args     Widget arguments.
		 * @param array $instance Saved values from database.
		 */
		public function widget($args, $instance) {
			global $wp_query;
			if (!is_post_type_archive('product') && !is_tax(get_object_taxonomies('product'))) {
				return;
			}
			$settings = apply_filters('rndapf_settings', get_option('rndapf_settings'));
			if(isset($settings['filters_turn_off'])){
				return;	
			}
			// enqueue necessary scripts
			wp_enqueue_style('rndapf-style');
			wp_enqueue_style('font-awesome');
			wp_enqueue_script('rndapf-script');
			
			if (empty($instance['query_type'])) {
				return;
			}

			$enable_multiple = (!empty($instance['enable_multiple'])) ? (bool)$instance['enable_multiple'] : '';
			$show_count = (!empty($instance['show_count'])) ? (bool)$instance['show_count'] : '';
			$enable_hierarchy = (!empty($instance['hierarchical'])) ? (bool)$instance['hierarchical'] : '';
			$show_children_only = (!empty($instance['show_children_only'])) ? (bool)$instance['show_children_only'] : '';
			$display_type = (!empty($instance['display_type'])) ? $instance['display_type'] : '';

			$taxonomy   = 'product_cat';
			$query_type = $instance['query_type'];
			$data_key   = ($query_type === 'and') ? 'product-cata' : 'product-cato';

			 // parse url
			 if( isset($_POST['query']) ){
                $url = sanitize_url($_POST['query']); 
    
                // Use parse_url() function to parse the URL 
                // and return an associative array which contains its various components 
                $url_components = parse_url($url); 

                // Use the parse_str() function to parse the 
                // string passed via the URL 
                parse_str($url_components['query'], $query); 

            }else{
                $url = $_SERVER['QUERY_STRING'];
                parse_str($url, $query);
            }
		
            $category  ='';
           $category = $wp_query->get_queried_object();
		   $term_id = $category->term_id;
           $term_link = get_term_link( $category->term_id );

			$attr_args = array(
				'taxonomy'           => $taxonomy,
				'data_key'           => $data_key,
				'query_type'         => $query_type,
				'enable_multiple'    => $enable_multiple,
				'show_count'         => $show_count,
				'enable_hierarchy'   => $enable_hierarchy,
				'show_children_only' => $show_children_only,
				'url_array'          => $url_array
			);

			// if display type list
			if ($display_type === 'list') { 
				$output = rndapf_list_terms($attr_args);
			} elseif ($display_type === 'dropdown') {
				$output = rndapf_dropdown_terms($attr_args);
			}
			
			$html = $output['html'];
			$found = $output['found'];

			extract($args);

			// Add class to before_widget from within a custom widget
			// http://wordpress.stackexchange.com/questions/18942/add-class-to-before-widget-from-within-a-custom-widget

			// if $selected_terms array is empty we will hide this widget totally
			if ($found === false) {
				$widget_class = 'rndapf-widget-hidden woocommerce rndapf-ajax-term-filter';
			} else {
				$widget_class = 'woocommerce rndapf-ajax-term-filter';
			}

			// no class found, so add it
			if (strpos($before_widget, 'class') === false) {
				$before_widget = str_replace('>', 'class="' . $widget_class . '"', $before_widget);
			}
			// class found but not the one that we need, so add it
			else {
				$before_widget = str_replace('class="', 'class="' . $widget_class . ' ', $before_widget);
			}

			echo $before_widget;

			if (!empty($instance['title'])) {
				echo $args['before_title'] . apply_filters('widget_title', $instance['title']). $args['after_title'];
			}

			$allowed_html =	array(
				'a' => array(
					'class' => array(),
					'href'  => array(),
					'rel'   => array(),
					'title' => array(),
					'data-key' => array(),
					'data-value' => array(),
					'data-multiple-filter' => array()
				),
				'div' => array(
					'class' => array(),
					'title' => array(),
					'style' => array(),
				),
				'li' => array(
					'class' => array(),
				),
				'ol' => array(
					'class' => array(),
				),
				'ul' => array(
					'class' => array(),
				)
			);
			
			echo wp_kses($html ,$allowed_html);

			echo $args['after_widget'];
		}

		/**
		 * Back-end widget form.
		 *
		 * @see WP_Widget::form()
		 *
		 * @param array $instance Previously saved values from database.
		 */
		public function form($instance) {
			?>
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>"><?php echo esc_html(__('Title:', 'rndapf')); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo (!empty($instance['title']) ? esc_attr($instance['title']) : ''); ?>">
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('display_type'); ?>"><?php echo esc_html(__('Display Type')) ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id('display_type'); ?>" name="<?php echo $this->get_field_name('display_type'); ?>">
					<option value="list" <?php echo ((!empty($instance['display_type']) && $instance['display_type'] === 'list') ? 'selected="selected"' : ''); ?>><?php echo esc_attr(__('List', 'rndapf')); ?></option>
					<!--option value="dropdown" <?php echo ((!empty($instance['display_type']) && $instance['display_type'] === 'dropdown') ? 'selected="selected"' : ''); ?>><?php echo esc_attr(__('Dropdown', 'rndapf')); ?></option -->
				</select>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('query_type'); ?>"><?php echo esc_html(__('Query Type')) ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id('query_type'); ?>" name="<?php echo $this->get_field_name('query_type'); ?>">
					<option value="and" <?php echo ((!empty($instance['query_type']) && $instance['query_type'] === 'and') ? 'selected="selected"' : ''); ?>><?php echo esc_attr(__('AND', 'rndapf')); ?></option>
					<option value="or" <?php echo ((!empty($instance['query_type']) && $instance['query_type'] === 'or') ? 'selected="selected"' : ''); ?>><?php echo esc_attr(__('OR', 'rndapf')); ?></option>
				</select>
			</p>
			<p>
				<input id="<?php echo $this->get_field_id('enable_multiple'); ?>" name="<?php echo $this->get_field_name('enable_multiple'); ?>" type="checkbox" value="1" <?php echo (!empty($instance['enable_multiple']) && $instance['enable_multiple'] == true) ? 'checked="checked"' : ''; ?>>
				<label for="<?php echo $this->get_field_id('enable_multiple'); ?>"><?php echo esc_html(__('Enable multiple filter', 'rndapf')); ?></label>
			</p>
			<p>
				<input id="<?php echo $this->get_field_id('show_count'); ?>" name="<?php echo $this->get_field_name('show_count'); ?>" type="checkbox" value="1" <?php echo (!empty($instance['show_count']) && $instance['show_count'] == true) ? 'checked="checked"' : ''; ?>>
				<label for="<?php echo $this->get_field_id('show_count'); ?>"><?php echo esc_html(__('Show count', 'rndapf')); ?></label>
			</p>
			<p>
				<input id="<?php echo $this->get_field_id('hierarchical'); ?>" name="<?php echo $this->get_field_name('hierarchical'); ?>" type="checkbox" value="1" <?php echo (!empty($instance['hierarchical']) && $instance['hierarchical'] == true) ? 'checked="checked"' : ''; ?>>
				<label for="<?php echo $this->get_field_id('hierarchical'); ?>"><?php echo esc_html(__('Show hierarchy', 'rndapf')); ?></label>
			</p>
			<p>
				<input id="<?php echo $this->get_field_id('show_children_only'); ?>" name="<?php echo $this->get_field_name('show_children_only'); ?>" type="checkbox" value="1" <?php echo (!empty($instance['show_children_only']) && $instance['show_children_only'] == true) ? 'checked="checked"' : ''; ?>>
				<label for="<?php echo $this->get_field_id('show_children_only'); ?>"><?php echo esc_html(__('Only show children of the current attribute', 'rndapf')); ?></label>
			</p>
			<?php
		}

		/**
		 * Sanitize widget form values as they are saved.
		 *
		 * @see WP_Widget::update()
		 *
		 * @param array $new_instance Values just sent to be saved.
		 * @param array $old_instance Previously saved values from database.
		 *
		 * @return array Updated safe values to be saved.
		 */
		public function update($new_instance, $old_instance) {
			$instance = array();
			$instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
			$instance['display_type'] = (!empty($new_instance['display_type'])) ? strip_tags($new_instance['display_type']) : '';
			$instance['query_type'] = (!empty($new_instance['query_type'])) ? strip_tags($new_instance['query_type']) : '';
			$instance['enable_multiple'] = (!empty($new_instance['enable_multiple'])) ? strip_tags($new_instance['enable_multiple']) : '';
			$instance['show_count'] = (!empty($new_instance['show_count'])) ? strip_tags($new_instance['show_count']) : '';
			$instance['hierarchical'] = (!empty($new_instance['hierarchical'])) ? strip_tags($new_instance['hierarchical']) : '';
			$instance['show_children_only'] = (!empty($new_instance['show_children_only'])) ? strip_tags($new_instance['show_children_only']) : '';
			return $instance;
		}
	}
}

// register widget
if (!function_exists('rndapf_register_category_filter_widget')) {
	function rndapf_register_category_filter_widget() {
		register_widget('RNDAPF_Category_Filter_Widget');
	}
	add_action('widgets_init', 'rndapf_register_category_filter_widget');
}