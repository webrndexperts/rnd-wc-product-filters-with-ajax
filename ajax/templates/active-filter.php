<?php 
			$found = false;
			$html = '';

            if (sizeof($active_filters) > 0) {
                $found = true;
                $found_filter = false;
				?>
               <div class="rndapf-active-filters">
				<?php
                foreach ($active_filters as $key => $active_filter) {
                    if ($key === 'term') {
                        $found_filter = true;
                        foreach ($active_filter as $data_key => $terms) {
                            foreach ($terms as $term_id => $term_name) {
                             ?>
                                <a href="javascript:void(0)" data-key="<?php echo  esc_attr($data_key); ?>" data-value="<?php echo esc_attr($term_id); ?>"> <?php echo esc_attr($term_name); ?> </a>
							<?php
                            }
                        }
                    }

                    if ($key === 'keyword') {
                        $found_filter = true;
						?>
                       <a href="javascript:void(0)" data-key="keyword"><?php echo esc_attr( __('Search For: ', 'rndapf')) . $active_filter ; ?> </a>
						<?php
                    }

                    if ($key === 'orderby') {
                        $found_filter = true;
						?>
                       <a href="javascript:void(0)" data-key="orderby"><?php echo esc_attr( __('Orderby: ', 'rndapf') . $active_filter ); ?> </a>
						<?php
                    }

                    if ($key === 'min_price') {
                        $found_filter = true;
						?>
                       <a href="javascript:void(0)" data-key="min-price"><?php echo esc_attr( __('Min Price: ', 'rndapf') . $active_filter); ?> </a>
						<?php
                    }

                    if ($key === 'max_price') {
                        $found_filter = true;
						?>
                       <a href="javascript:void(0)" data-key="max-price"><?php echo esc_attr( __('Max Price: ', 'rndapf') . $active_filter);?> </a>
						<?php
                    }
                    /*
                    if ($key === 'min-rating') {
                          $found_filter = true;
                        $html .= '<a href="javascript:void(0)" data-key="min-rating">' . __('Min Rating: ', 'rndapf') . $active_filter . '</a>';
                    }

                    if ($key === 'max-rating') {
                          $found_filter = true;
                        $html .= '<a href="javascript:void(0)" data-key="max-rating">' . __('Max Rating: ', 'rndapf') . $active_filter . '</a>';
                    }
                    */
                }

                if (!empty($instance['button_text'])) {
                    if (defined('SHOP_IS_ON_FRONT')) {
                        $link = home_url();
                    } elseif (is_post_type_archive('product') || is_page(wc_get_page_id('shop'))) {
                        $link = get_post_type_archive_link('product');
                    } else {
                        $link = get_term_link(get_query_var('term'), get_query_var('taxonomy'));
                    }

                    /**
                     * Search Arg.
                     * To support quote characters, first they are decoded from &quot; entities, then URL encoded.
                     */
                    if (get_search_query()) {
                        $link = add_query_arg('s', rawurlencode(htmlspecialchars_decode(get_search_query())), $link);
                    }

                    // Post Type Arg
                    if (isset($_GET['post_type'])) {
                        $link = add_query_arg('post_type', wc_clean($_GET['post_type']), $link);
                    }
					?>
                   <a href="javascript:void(0)" class="reset" data-location="<?php echo esc_attr( $link ); ?>"> <?php echo esc_attr($instance['button_text']);?> </a>
					<?php 
                }else{
                    if (defined('SHOP_IS_ON_FRONT')) {
                        $link = home_url();
                    } elseif (is_post_type_archive('product') || is_page(wc_get_page_id('shop'))) {
                        $link = get_post_type_archive_link('product');
                    } else {
                        $link = get_term_link(get_query_var('term'), get_query_var('taxonomy'));
                    }

                    if( $found_filter){
						?>
						 <a href="javascript:void(0)" class="reset" data-location="<?php echo esc_attr($link);?>"><?php echo esc_attr( 'Reset all' ); ?></a>
						 <?php
					}
                   
                }
				?>
               </div>
				<?php
            }
           
                ?>