jQuery(document).ready(function($) {
    if (typeof rndapf_price_filter_params === 'undefined') {
        return false;
    }

    var priceSlider = document.getElementById('rndapf-noui-slider1');
    var rating = [];
    $(document).on('click', '.stars-count', function(event) {
        event.preventDefault();
        $('.stars-count').removeClass('selected_stars');
        var min_val = parseInt($(this).attr('data-min')),
            max_val = parseInt($(this).attr('data-max'));
        rating = [min_val, max_val];
        $post_data['rating'] = rating;
        $(this).prevAll('.stars-count').addClass('selected_stars');
        $(this).addClass('selected_stars');
        // filter products without reinitializing price slider
        rndapfFilterProducts($post_data);
    });
    // price slider
    rndapfInitPriceSlider = function() {
        if ($('#rndapf-noui-slider1').length) {

            var min_val = parseInt($(priceSlider).attr('data-min')),
                max_val = parseInt($(priceSlider).attr('data-max')),
                set_min_val = parseInt($(priceSlider).attr('data-set-min')),
                set_max_val = parseInt($(priceSlider).attr('data-set-max'));
            rating = [min_val, min_val];
            $post_data['rating'] = rating;

            if (!set_min_val) {
                set_min_val = min_val;
            }

            if (!set_max_val) {
                set_max_val = max_val;
            }

            noUiSlider.create(priceSlider, {
                start: [set_min_val, set_max_val],
                step: 1,
                margin: 1,
                range: {
                    'min': min_val,
                    'max': max_val
                }
            });

            var min_val_holder = document.getElementById('rndapf-noui-slider-value-min1'),
                max_val_holder = document.getElementById('rndapf-noui-slider-value-max1');

            priceSlider.noUiSlider.on('update', function(values, handle) {
                if (handle) {
                    var value = parseInt(values[handle]);
                    // $post_data[max_val_holder] = value;
                    $(document).trigger('update_rndapf_slider_vals', [max_val_holder, value]);
                } else {
                    var value = parseInt(values[handle]);
                    // $post_data[min_val_holder] = value;
                    $(document).trigger('update_rndapf_slider_vals', [min_val_holder, value]);
                }
                $post_data['rating'] = values;
            });

            priceSlider.noUiSlider.on('change', function(values, handle) {
                var params = rndapfGetUrlVars();
                if (handle) {
                    var max = parseInt(values[handle]),
                        filter_key = 'max-rating';

                    // remove this parameter if set value is equal to max val
                    if (max == max_val) {
                        var query = rndapfRemoveQueryStringParameter(filter_key);
                        //history.pushState({}, '', query);
                        $post_data['query'] = query;

                    } else {
                        $post_data[filter_key] = max;
                        rndapfUpdateQueryStringParameter(filter_key, max);
                    }
                } else {
                    var min = parseInt(values[handle]),
                        filter_key = 'min-rating';

                    // remove this parameter if set value is equal to min val
                    if (min == min_val) {
                        var query = rndapfRemoveQueryStringParameter(filter_key);
                        // history.pushState({}, '', query);
                        $post_data['query'] = query;
                    } else {
                        $post_data[filter_key] = min;
                        rndapfUpdateQueryStringParameter(filter_key, min);
                    }
                }
                $post_data['rating'] = values;

                // filter products without reinitializing price slider
                rndapfFilterProducts($post_data);
            });
        }
    }

    // position currency symbol
    $(document).bind('update_rndapf_slider_vals', function(event, value_holder, value) {
        // if WooCommerce Currency Switcher plugin is activated
        if (typeof woocs_current_currency !== 'undefined') {
            if (woocs_current_currency.position === 'left') {
                $(value_holder).html(value);
            } else if (woocs_current_currency.position === 'left_space') {
                $(value_holder).html(value);
            } else if (woocs_current_currency.position === 'right') {
                $(value_holder).html(value);
            } else if (woocs_current_currency.position === 'right_space') {
                $(value_holder).html(value);
            }
        } else {
            if (rndapf_price_filter_params.currency_pos === 'left') {
                $(value_holder).html(value);
            } else if (rndapf_price_filter_params.currency_pos === 'left_space') {
                $(value_holder).html(value);
            } else if (rndapf_price_filter_params.currency_pos === 'right') {
                $(value_holder).html(value);
            } else if (rndapf_price_filter_params.currency_pos === 'right_space') {
                $(value_holder).html(value);
            }
        }
    });

    // initialize price slider
    rndapfInitPriceSlider();
});