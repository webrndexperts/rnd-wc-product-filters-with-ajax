<?php
/**
 * Necessary functions in WC Ajax Product Filter plugin.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Get child term ids for given term.
 * 
 * @param  int $term_id
 * @param  string $taxonomy
 * @return array
 */
if (!function_exists('rndapf_get_term_childs')) {
	function rndapf_get_term_childs($term_id, $taxonomy) {
		$transient_name = 'rndapf_term_childs_' . md5(sanitize_key($taxonomy) . sanitize_key($term_id));

		if (false === ($term_childs = get_transient($transient_name))) {
			$term_childs = get_term_children($term_id, $taxonomy);
			set_transient($transient_name, $term_childs, RNDAPF_CACHE_TIME);
		}

		return (array)$term_childs;
	}
}

/**
 * Get ancestor term ids for given term.
 * 
 * @param  int $term_id
 * @param  string $taxonomy
 * @return array
 */
if (!function_exists('rndapf_get_term_ancestors')) {
	function rndapf_get_term_ancestors($term_id, $taxonomy) {
		$transient_name = 'wcafp_term_ancestors_' . md5(sanitize_key($taxonomy). sanitize_key($term_id));

		if (false === ($term_ancestors = get_transient($transient_name))) {
			$term_ancestors = get_ancestors($term_id, $taxonomy);
			set_transient($transient_name, $term_ancestors);
		}

		// if found then add current term id to this array
		if (sizeof($term_ancestors) > 0) {
			array_push($term_ancestors, $term_id);
		}

		return (array)$term_ancestors;
	}
}

/**
 * Get details for given term.
 * 
 * @param  int $term_id
 * @param  string $taxonomy
 * @return mixed
 */
if (!function_exists('rndapf_get_term_data')) {
	function rndapf_get_term_data($term_id, $taxonomy) {
		$transient_name = 'rndapf_term_data_' . md5(sanitize_key($taxonomy) . sanitize_key($term_id));

		if (false === ($term_data = get_transient($transient_name))) {
			$term_data = get_term($term_id, $taxonomy);
			set_transient($transient_name, $term_data, RNDAPF_CACHE_TIME);
		}

		return $term_data;
	}
}

/**
 * Get product ids for given term.
 * 
 * @param  int $term_id
 * @param  string $taxonomy
 * @return array
 */
if (!function_exists('rndapf_get_term_objects')) {
	function rndapf_get_term_objects($term_id, $taxonomy) {
		global $rndapf;
		$unfiltered_product_ids = $rndapf->unfilteredProductIds();

		$transient_name = 'rndapf_term_objects_' . md5(sanitize_key($taxonomy) . sanitize_key($term_id));

		if (false === ($objects_in_term = get_transient($transient_name))) {
			$objects_in_term = get_objects_in_term($term_id, $taxonomy);
			$objects_in_term = array_intersect($objects_in_term, $unfiltered_product_ids);
			set_transient($transient_name, $objects_in_term, RNDAPF_CACHE_TIME);
		}

		return (array)$objects_in_term;
	}
}

/**
 * Get product ids for given term, it will also pull the products from its child terms.
 * 
 * @param  int $term_id
 * @param  string $taxonomy
 * @return array
 */
if (!function_exists('rndapf_get_term_products')) {
	function rndapf_get_term_products($term_id, $taxonomy) {
		$products_in_term = rndapf_get_term_objects($term_id, $taxonomy);
		$term_childs = rndapf_get_term_childs($term_id, $taxonomy);

		if (is_array($term_childs) && sizeof($term_childs) > 0) {
			foreach ($term_childs as $term_child) {
				$products_in_term = array_merge($products_in_term, rndapf_get_term_objects($term_child, $taxonomy));
			}
		}

		return array_unique($products_in_term);
	}
}

/**
 * Function for clearing old transients stored by our plugin.
 */
if (!function_exists('rndapf_clear_transients')) {
	function rndapf_clear_transients() {
		global $wpdb;
		$sql = "DELETE FROM $wpdb->options WHERE `option_name` LIKE ('%\_transient_rndapf\_%') OR `option_name` LIKE ('%\_transient_timeout_rndapf\_%')";
		return $wpdb->query($sql);
	}
}

/**
 * rndapf_list_sub_terms function
 * 
 * @param  array $sub_term_args
 * @param  bool $found
 * @return mixed
 */
if (!function_exists('rndapf_list_sub_terms')) {
	function rndapf_list_sub_terms($sub_term_args, $found) {
		global $rndapf;
		$filtered_product_ids = $rndapf->filteredProductIds();
		$unfiltered_product_ids = $rndapf->unfilteredProductIds();
		$chosen_filters = $rndapf->getChosenFilters();

		extract($sub_term_args);

		$html = '<ul class="children">';

		foreach ($sub_term_ids as $sub_term_id) {
			$sub_term_data = rndapf_get_term_data($sub_term_id, $taxonomy);
			if ($sub_term_data && ($sub_term_data->parent == $parent_term_id)) {
				$_parent_term_id = $sub_term_data->term_id;
				$_parent_term_name = $sub_term_data->name;

				// get sub term ids for this term
				$_sub_term_ids = rndapf_get_term_childs($_parent_term_id, $taxonomy);

				// get product ids for this term
				$products_in_term = rndapf_get_term_products($_parent_term_id, $taxonomy);

				if ($query_type === 'and') {
					// count product ids those are not present in $filtered_product_ids array
					$count = sizeof(array_intersect($products_in_term, $filtered_product_ids));
				} else {
					// count product ids those are present in $unfiltered_product_ids
					$count = sizeof(array_intersect($products_in_term, $unfiltered_product_ids));
				}

				$force_show = false;

				if (sizeof($ancestors) > 0) {
					if (in_array($_parent_term_id, $ancestors)) {
						$force_show = true;
					}
				}

				if ($count > 0 || $force_show === true) {
					$found = true;

					if (in_array($_parent_term_id, $term_ids)) {
						$html .= '<li class="chosen">';
					} else {
						$html .= '<li>';
					}

						$html .= '<a href="javascript:void(0)" data-key="' . $data_key . '" data-value="' . $_parent_term_id . '" data-multiple-filter="' . $enable_multiple . '">' . $_parent_term_name . '</a>';

						if ($show_count === true) {
							$html .= '<span class="count">(' . $count . ')</span>';
						}

						if (($enable_hierarchy === true && $show_children_only !== true) || ($show_children_only == true && (in_array($_parent_term_id, $term_ids) || $force_show === true))) {

							if (sizeof($_sub_term_ids) > 0) {
								$sub_term_args = array(
									'taxonomy'           => $taxonomy,
									'data_key'           => $data_key,
									'query_type'         => $query_type,
									'enable_multiple'    => $enable_multiple,
									'show_count'         => $show_count,
									'enable_hierarchy'   => $enable_hierarchy,
									'show_children_only' => $show_children_only,
									'parent_term_id'     => $_parent_term_id,
									'sub_term_ids'       => $_sub_term_ids,
									'term_ids'           => $term_ids,
									'ancestors'          => $ancestors
								);

								$results = rndapf_list_sub_terms($sub_term_args, $found);

								$html .= $results['html'];
								$found = $results['found'];
							}

						}

					$html .= '</li>';
				}
			}
		}

		$html .= '</ul>';

		return array(
			'html'  => $html,
			'found' => $found
		);
	}
}

/**
 * rndapf_list_terms function
 * 
 * @param  array $attr_args
 * @return mixed
 */
if (!function_exists('rndapf_list_terms')) {
	function rndapf_list_terms($attr_args) {
		global $rndapf;
		$filtered_product_ids = $rndapf->filteredProductIds();
		$unfiltered_product_ids = $rndapf->unfilteredProductIds();
		$chosen_filters = $rndapf->getChosenFilters();

		extract($attr_args);

		$parent_args = array(
			'orderby'    => 'name',
			'order'      => 'ASC',
			'hide_empty' => true
		);

		if ($enable_hierarchy === true) {
			$parent_args['parent'] = 0;
		}

		$parent_terms = get_terms($taxonomy, $parent_args);
		
		$html = '';
		$found = false;

		if (sizeof($parent_terms) > 0) {
			$html .= '<div class="rndapf-layered-nav">';
			$html .= '<ul>';

			// store term ids from url for this attribute
			// example: attra_size=9,29,45
			$term_ids = array();
			
			if (key_exists($data_key, $url_array) && !empty($url_array[$data_key])) {
				$term_ids = explode(',', $url_array[$data_key]);
			}

			// store the ancestor ids for this term
			$term_ancestors = $chosen_filters['term_ancestors'];
			$ancestors = array();

			if (sizeof($term_ancestors) > 0 && key_exists($data_key, $term_ancestors)) {
				foreach ($term_ancestors[$data_key] as $chosen_filter) {
					foreach ($chosen_filter as $ancestor) {
						array_push($ancestors, $ancestor);
					}
				}
			}

			$ancestors = array_unique($ancestors);

			foreach ($parent_terms as $parent_term) {
				$parent_term_id = $parent_term->term_id;
				// get sub term ids for this term
				$sub_term_ids = rndapf_get_term_childs($parent_term_id, $taxonomy);

				// get product ids for this term
				$products_in_term = rndapf_get_term_products($parent_term_id, $taxonomy);

				if ($query_type === 'and') {
					// count product ids those are not present in $filtered_product_ids array
					$count = sizeof(array_intersect($products_in_term, $filtered_product_ids));
				} else {
					// count product ids those are present in $unfiltered_product_ids
					$count = sizeof(array_intersect($products_in_term, $unfiltered_product_ids));
				}

				$force_show = false;

				// if this term id is present in $term_ids array we will force
				if (in_array($parent_term_id, $term_ids)) {
					$force_show = true;
				}
				// if child term is selected we will force
				elseif (sizeof($ancestors) > 0 && in_array($parent_term_id, $ancestors)) {
					$force_show = true;
				}

				if ($count > 0 || $force_show === true) {
					$found = true;

					if (in_array($parent_term_id, $term_ids)) {
						$html .= '<li class="chosen">';
					} else {
						$html .= '<li>';
					}

						$html .= '<a href="javascript:void(0)" data-key="' . $data_key . '" data-value="' . $parent_term_id . '" data-multiple-filter="' . $enable_multiple . '">' . $parent_term->name . '</a>';

						if ($show_count === true) {
							$html .= '<span class="count">(' . $count . ')</span>';
						}
					
						if (($enable_hierarchy === true && $show_children_only !== true) || ($show_children_only === true && (in_array($parent_term_id, $term_ids) || $force_show === true))) {

							if (sizeof($sub_term_ids) > 0) {
								$sub_term_args = array(
									'taxonomy'           => $taxonomy,
									'data_key'           => $data_key,
									'query_type'         => $query_type,
									'enable_multiple'    => $enable_multiple,
									'show_count'         => $show_count,
									'enable_hierarchy'   => $enable_hierarchy,
									'show_children_only' => $show_children_only,
									'parent_term_id'     => $parent_term_id,
									'sub_term_ids'       => $sub_term_ids,
									'term_ids'           => $term_ids,
									'ancestors'          => $ancestors
								);

								$results = rndapf_list_sub_terms($sub_term_args, $found);
								
								$html .= $results['html'];
								$found = $results['found'];
							}

						}

					$html .= '</li>';
				}
			}

			$html .= '</ul>';
			$html .= '</div>';
		}

		return array(
			'html'  => $html,
			'found' => $found
		);
	}
}

/**
 * rndapf_dropdown_sub_terms function
 * 
 * @param  array $sub_term_args
 * @param  bool $found
 * @param  bool $depth
 * @return mixed
 */
if (!function_exists('rndapf_dropdown_sub_terms')) {
	function rndapf_dropdown_sub_terms($sub_term_args, $found, $depth) {
		global $rndapf;
		$filtered_product_ids = $rndapf->filteredProductIds();
		$unfiltered_product_ids = $rndapf->unfilteredProductIds();
		$chosen_filters = $rndapf->getChosenFilters();

		extract($sub_term_args);

		$html = '';

		foreach ($sub_term_ids as $sub_term_id) {
			$sub_term_data = rndapf_get_term_data($sub_term_id, $taxonomy);
			if ($sub_term_data && ($sub_term_data->parent == $parent_term_id)) {
				$_parent_term_id = $sub_term_data->term_id;
				$_parent_term_name = $sub_term_data->name;

				// get sub term ids for this term
				$_sub_term_ids = rndapf_get_term_childs($_parent_term_id, $taxonomy);

				// get product ids for this term
				$products_in_term = rndapf_get_term_products($_parent_term_id, $taxonomy);

				if ($query_type === 'and') {
					// count product ids those are not present in $filtered_product_ids array
					$count = sizeof(array_intersect($products_in_term, $filtered_product_ids));
				} else {
					// count product ids those are present in $unfiltered_product_ids
					$count = sizeof(array_intersect($products_in_term, $unfiltered_product_ids));
				}

				$force_show = false;

				if (sizeof($ancestors) > 0) {
					if (in_array($_parent_term_id, $ancestors)) {
						$force_show = true;
					}
				}

				if ($count > 0 || $force_show === true) {
					$found = true;

					$selected = (in_array($_parent_term_id, $term_ids)) ? ' selected="selected"' : '';

					if ($show_count === true) {
						$html .= '<option value="' . $_parent_term_id . '"' . $selected . ' data-depth="' . $depth . '">' . $_parent_term_name . ' (' . $count . ')</option>';
					} else {
						$html .= '<option value="' . $_parent_term_id . '"' . $selected . ' data-depth="' . $depth . '">' . $_parent_term_name . '</option>';
					}

					if (($enable_hierarchy === true && $show_children_only !== true) || ($show_children_only == true && (in_array($_parent_term_id, $term_ids) || $force_show === true))) {

						if (sizeof($_sub_term_ids) > 0) {
							$sub_term_args = array(
								'taxonomy'           => $taxonomy,
								'data_key'           => $data_key,
								'query_type'         => $query_type,
								'enable_multiple'    => $enable_multiple,
								'show_count'         => $show_count,
								'enable_hierarchy'   => $enable_hierarchy,
								'show_children_only' => $show_children_only,
								'parent_term_id'     => $_parent_term_id,
								'sub_term_ids'       => $_sub_term_ids,
								'term_ids'           => $term_ids,
								'ancestors'          => $ancestors
							);

							$results = rndapf_dropdown_sub_terms($sub_term_args, $found, $depth + 1);

							$html .= $results['html'];
							$found = $results['found'];
						}

					}
				}
			}
		}

		return array(
			'html'  => $html,
			'found' => $found
		);
	}
}

/**
 * rndapf_dropdown_terms function
 * 
 * @param  array $attr_args
 * @return mixed
 */
if (!function_exists('rndapf_dropdown_terms')) {
	function rndapf_dropdown_terms($attr_args) {
		global $rndapf;
		$filtered_product_ids = $rndapf->filteredProductIds();
		$unfiltered_product_ids = $rndapf->unfilteredProductIds();
		$chosen_filters = $rndapf->getChosenFilters();

		extract($attr_args);

		$parent_args = array(
			'orderby'    => 'name',
			'order'      => 'ASC',
			'hide_empty' => true
		);

		if ($enable_hierarchy === true) {
			$parent_args['parent'] = 96;
		}

		$parent_terms = get_terms($taxonomy, $parent_args);
		
		$html = '';
		$found = false;

		if (preg_match('/^attr/', $data_key)) {
			$attr = str_replace(array('attra-', 'attro-'), '', $data_key);
			$placeholder = sprintf(__('Choose %s', 'rndapf'), $attr);
		} elseif (preg_match('/^product-cat/', $data_key)) {
			$placeholder = sprintf(__('Choose category', 'rndapf'));
		}

		if ($enable_multiple === true) {
			$multiple = 'multiple="multiple"';
		} else {
			$multiple = '';
		}

		if (sizeof($parent_terms) > 0) {
			// required scripts
			wp_enqueue_style('rndapf-select2');
			wp_enqueue_script('rndapf-select2');

			if ($enable_multiple === true) {
				$select_holder_class = 'rndapf-select2 rndapf-select2-multiple';
			} else {
				$select_holder_class = 'rndapf-select2 rndapf-select2-single';
			}

			$html .= '<div class="rndapf-dropdown-nav">';
				$html .= '<select class="' . $select_holder_class . '" name="' . $data_key . '" style="width: 100%;" ' . $multiple . ' data-placeholder="' . $placeholder . '">';

				if ($enable_multiple !== true) {
					$html .= '<option></option>';
				}

				// store term ids from url for this attribute
				// example: attra_size=9,29,45
				$term_ids = array();
				
				if (key_exists($data_key, $url_array) && !empty($url_array[$data_key])) {
					$term_ids = explode(',', $url_array[$data_key]);
				}

				// store the ancestor ids for this term
				$term_ancestors = $chosen_filters['term_ancestors'];
				$ancestors = array();

				if (sizeof($term_ancestors) > 0 && key_exists($data_key, $term_ancestors)) {
					foreach ($term_ancestors[$data_key] as $chosen_filter) {
						foreach ($chosen_filter as $ancestor) {
							array_push($ancestors, $ancestor);
						}
					}
				}

				$ancestors = array_unique($ancestors);

				foreach ($parent_terms as $parent_term) {
					$parent_term_id = $parent_term->term_id;
					// get sub term ids for this term
					$sub_term_ids = rndapf_get_term_childs($parent_term_id, $taxonomy);

					// get product ids for this term
					$products_in_term = rndapf_get_term_products($parent_term_id, $taxonomy);

					if ($query_type === 'and') {
						// count product ids those are not present in $filtered_product_ids array
						$count = sizeof(array_intersect($products_in_term, $filtered_product_ids));
					} else {
						// count product ids those are present in $unfiltered_product_ids
						$count = sizeof(array_intersect($products_in_term, $unfiltered_product_ids));
					}

					$force_show = false;

					// if this term id is present in $term_ids array we will force
					if (in_array($parent_term_id, $term_ids)) {
						$force_show = true;
					}
					// if child term is selected we will force
					elseif (sizeof($ancestors) > 0 && in_array($parent_term_id, $ancestors)) {
						$force_show = true;
					}

					if ($count > 0 || $force_show === true) {
						$found = true;

						$selected = (in_array($parent_term_id, $term_ids)) ? ' selected="selected"' : '';

						if ($show_count === true) {
							$html .= '<option value="' . $parent_term_id . '"' . $selected . '>' . $parent_term->name . ' (' . $count . ')</option>';
						} else {
							$html .= '<option value="' . $parent_term_id . '"' . $selected . '>' . $parent_term->name . '</option>';
						}
						
						if (($enable_hierarchy === true && $show_children_only !== true) || ($show_children_only === true && (in_array($parent_term_id, $term_ids) || $force_show === true))) {

							if (sizeof($sub_term_ids) > 0) {
								$sub_term_args = array(
									'taxonomy'           => $taxonomy,
									'data_key'           => $data_key,
									'query_type'         => $query_type,
									'enable_multiple'    => $enable_multiple,
									'show_count'         => $show_count,
									'enable_hierarchy'   => $enable_hierarchy,
									'show_children_only' => $show_children_only,
									'parent_term_id'     => $parent_term_id,
									'sub_term_ids'       => $sub_term_ids,
									'term_ids'           => $term_ids,
									'ancestors'          => $ancestors
								);

								$results = rndapf_dropdown_sub_terms($sub_term_args, $found, $depth = 1);
								
								$html .= $results['html'];
								$found = $results['found'];
							}

						}
					}
				}

				$html .= '</select>';
			$html .= '</div>';
		}

		return array(
			'html'  => $html,
			'found' => $found
		);
	}
}