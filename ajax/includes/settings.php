<?php

/**

 * HTML markup for Settings page.

 */



// Exit if accessed directly

if (!defined('ABSPATH')) {

	exit;

}

?>

<div class="wrap">
	<style>
		.pro_version {
			background-color: navajowhite;
			margin: 10px 1px 1px 10px;
			padding: 5px 5px 20px 22px;
		}		
.pro_version ul {
  padding: 5px;
}

.pro_version ul li {
  color: green;
  margin: 10px;
  font-size: 18px;
}

a.pro_link {
    font-style: oblique;
    padding-left: 10px;
    font-size: 22px;
    text-decoration: none;
}
	</style>
	<div class="pro_version">

	<h1><?php _e('RND Ajax Product Filter Plugin Pro Version Features', RNDAPF_LOCALE); ?></h1>
		<ul>
		<li>1. Category Filter Widgets</li>
		<li>2. Price Filter Widgets </li>
		<li>3. Reset Filter Widgets</li>
		<li>4. Turn Off Filters settings. </li>
		<li>5. Other settings options. </li>

	</ul>

		<a class="pro_link" href="https://codecanyon.net/item/woocommerce-product-filter-with-ajax/36349498"  target="_blank">You can purchase Pro Version here for more features</a>

</div>	

	<h1><?php _e('RND Ajax Product Filter', RNDAPF_LOCALE); ?></h1>

	<form method="post" action="options.php">

		<?php

		settings_fields('rndapf_settings');

		do_settings_sections('rndapf_settings');

		// check if filter is applied

		$settings = apply_filters('rndapf_settings', get_option('rndapf_settings'));

		?>



		<?php if (has_filter('rndapf_settings')): ?>

			<p><span class="dashicons dashicons-info"></span> <?php _e('Filter has been applied and that may modify the settings below.', RNDAPF_LOCALE); ?></p>

		<?php endif ?>



		<table class="form-table">

			<tr>

				<th scope="row"><?php _e('Shop loop container', RNDAPF_LOCALE); ?></th>

				<td>

					<input type="text" name="rndapf_settings[shop_loop_container]" size="50" value="<?php echo esc_attr($settings['shop_loop_container']); ?>">

					<br />

					<span><?php _e('Selector for tag that is holding the shop loop. Most of cases you won\'t need to change this.', RNDAPF_LOCALE); ?></span>

				</td>

			</tr>

			<tr>

				<th scope="row"><?php _e('No products container', RNDAPF_LOCALE); ?></th>

				<td>

					<input type="text" name="rndapf_settings[not_found_container]" size="50" value="<?php echo esc_attr($settings['not_found_container']); ?>">

					<br />

					<span><?php _e('Selector for tag that is holding no products found message. Most of cases you won\'t need to change this.', RNDAPF_LOCALE); ?></span>

				</td>

			</tr>

			<tr>

				<th scope="row"><?php _e('Pagination container', RNDAPF_LOCALE); ?></th>

				<td>

					<input type="text" name="rndapf_settings[pagination_container]" size="50" value="<?php echo esc_attr($settings['pagination_container']); ?>">

					<br />

					<span><?php _e('Selector for tag that is holding the pagination. Most of cases you won\'t need to change this.', RNDAPF_LOCALE); ?></span>

				</td>

			</tr>

			<tr>

				<th scope="row"><?php _e('Overlay background color', RNDAPF_LOCALE); ?></th>

				<td>

					<input type="text" name="rndapf_settings[overlay_bg_color]" size="50" value="<?php echo esc_attr($settings['overlay_bg_color']); ?>">

					<br />

					<span><?php _e('Change this color according to your theme, eg: #fff', RNDAPF_LOCALE); ?></span>

				</td>

			</tr>

			<tr>

				<th scope="row"><?php _e('Product sorting', RNDAPF_LOCALE); ?></th>

				<td>

					<input type="checkbox" name="rndapf_settings[sorting_control]" value="1" <?php (!empty($settings['sorting_control'])) ? checked(1, $settings['sorting_control'], true) : ''; ?>>

					<span><?php _e('Check if you want to sort your products via ajax.', RNDAPF_LOCALE); ?></span>

				</td>

			</tr>

			<tr style="display:none;">

                    <th scope="row"><?php _e('Show load button', RNDAPF_LOCALE); ?></th>

                    <td>

                        <input name="rndapf_settings[show_load_more_btn]" type='checkbox' value='1' <?php if( @$settings['show_load_more_btn'] ) echo "checked='checked'";?>/>

                        <span> <?php _e('If you want to show load more button to load more products ', RNDAPF_LOCALE); ?></span>

                    </td>

                </tr>

			<tr>

			

				<th scope="row"><?php _e('Scroll to top', RNDAPF_LOCALE); ?></th>

				<td>

					<input type="checkbox" name="rndapf_settings[scroll_to_top]" value="1" <?php (!empty($settings['scroll_to_top'])) ? checked(1, $settings['scroll_to_top'], true) : ''; ?>>

					<span><?php _e('Check if to enable scroll to top after updating shop loop.', RNDAPF_LOCALE); ?></span>

				</td>

			</tr>

			<tr>

				<th scope="row"><?php _e('Scroll to top offset', RNDAPF_LOCALE); ?></th>

				<td>

					<input type="text" name="rndapf_settings[scroll_to_top_offset]" size="50" value="<?php echo esc_attr($settings['scroll_to_top_offset']); ?>">

					<br />

					<span><?php _e('You need to change this value to match with your theme, eg: 100', RNDAPF_LOCALE); ?></span>

				</td>

			</tr>

			<tr>

				<th scope="row"><?php _e('Custom JavaScript after update', RNDAPF_LOCALE); ?></th>

				<td>

					<textarea name="rndapf_settings[custom_scripts]" rows="5" cols="70"><?php echo esc_attr($settings['custom_scripts']); ?></textarea>

					<br />

					<span><?php _e('If you want to add custom scripts that would be loaded after updating shop loop, eg: alert("hello");', RNDAPF_LOCALE); ?></span>

				</td>

			</tr>

			<tr style="display:none;">

                    <th scope="row"><?php _e('Turn all filters off', RNDAPF_LOCALE); ?></th>

                    <td>

                        <input name="rndapf_settings[filters_turn_off]" type='checkbox' value='1' <?php if( @$settings['filters_turn_off'] ) echo "checked='checked'";?>/>

                        <span> <?php _e('If you want to hide filters without losing current configuration just turn them off', RNDAPF_LOCALE); ?></span>

                    </td>

                </tr>

			<tr>

		</table>

		<?php submit_button() ?>

	</form>

</div>