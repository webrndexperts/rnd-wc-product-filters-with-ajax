var $post_data = {};
jQuery(document).ready(function($) {
    // return false if rndapf_params variable is not found
    if (typeof rndapf_params === 'undefined') {
        return false;
    }

    // store widget ids those will be replaced with new data
    var widgets = {};

    $('.rndapf-ajax-term-filter').each(function(index) {
        var widget_id = $(this).attr('id');
        widgets[index] = widget_id;
    });

    // scripts to run before updating shop loop
    rndapfBeforeUpdate = function() {
        var overlay_color;

        if (rndapf_params.overlay_bg_color.length) {
            overlay_color = rndapf_params.overlay_bg_color;
        } else {
            overlay_color = '#fff';
        }

        var markup = '<div class="rndapf-before-update" style="background-color: ' + overlay_color + '"></div>',
            holder,
            top_scroll_offset = 0;
        if ($(rndapf_params.shop_loop_container.length)) {
            holder = rndapf_params.shop_loop_container;
        } else if ($(rndapf_params.not_found_container).length) {
            holder = rndapf_params.not_found_container;
        }

        if (holder.length) {
            // show loading image
            $(markup).prependTo(holder);

            // scroll to top
            if (typeof rndapf_params.scroll_to_top !== 'undefined' && rndapf_params.scroll_to_top == true) {
                var scroll_to_top_offset,
                    top_scroll_offset;

                if (typeof rndapf_params.scroll_to_top_offset !== 'undefined' && rndapf_params.scroll_to_top_offset.length) {
                    scroll_to_top_offset = parseInt(rndapf_params.scroll_to_top_offset);
                } else {
                    scroll_to_top_offset = 100;
                }

                top_scroll_offset = $(holder).offset().top - scroll_to_top_offset;

                if (top_scroll_offset < 0) {
                    top_scroll_offset = 0;
                }

                $('html, body').animate({ scrollTop: top_scroll_offset }, 'slow');
            }
        }

    }

    // scripts to run after updating shop loop
    rndapfAfterUpdate = function() {}

    // load filtered products
    rndapfFilterProducts = function(parameters = []) {
        // run before update function: show a loading image and scroll to top
        var link = $(rndapf_params.shop_loop_container).attr('data-permalink');
        var category = $(rndapf_params.shop_loop_container).attr('data-category');
        parameters['action'] = 'filter_products';
        parameters['link'] = link;
        parameters['category'] = category;
        console.log(' --Parameters-- ', parameters);
        var request = $.ajax({
            url: rndapf_price_filter_params.ajaxurl, // AJAX handler
            data: parameters,
            type: 'POST',
            dataType: 'json',
            beforeSend: function(xhr) {
                rndapfBeforeUpdate();
            },
            success: function(data) {
                if (data) {
                    console.log('-- Response -- ', data);

                    var $data = jQuery(data.products),
                        shop_loop = rndapf_params.shop_loop_container,
                        not_found = rndapf_params.not_found_container;

                    $.each(widgets, function(index, id) {
                        var single_widget = $data.find('#' + id),
                            single_widget_class = $(single_widget).attr('class');
                        // update class
                        $('#' + id).attr('class', single_widget_class);
                        // update widget
                        $('#' + id).html(single_widget.html());
                    });
                    // replace old shop loop with new one
                    if ($(rndapf_params.shop_loop_container).length) {

                        if (data.found) {
                            if (data.load_more) {
                                $(rndapf_params.shop_loop_container).find('.products').append(data.products);
                            } else {
                                $(rndapf_params.shop_loop_container).find('.products').replaceWith(data.products);
                            }
                            $(rndapf_params.shop_loop_container).find('.woocommerce-pagination').replaceWith(data.pagination);
                            $(rndapf_params.shop_loop_container).find('.woocommerce-result-count').replaceWith(data.result_counts);

                            if ($('div.rndapf-ajax-active-filter').length > 0) {
                                $('div.rndapf-ajax-active-filter').removeClass('rndapf-widget-hidden');
                                $('div.rndapf-ajax-active-filter').html(data.active_filter);
                            }
                            $.each($("span.page-numbers"), function(index, item) {
                                var page = $(this).text();
                                if (page == data.current_page) {
                                    if (parameters['query']) {
                                        url = parameters['query'];
                                    } else {
                                        url = rndapfFixPagination();
                                    }
                                    var anchor = $(this);
                                    console.log('anchor', anchor);
                                    var tt = anchor.text();
                                    var link = $('<a/>');
                                    link.attr('href', url);
                                    link.text(parseInt(tt));
                                    anchor.html(link).addClass('current');
                                    $(this).parent().siblings().removeClass("active");
                                    $(this).parent().addClass("active");
                                    $(this).addClass('current');

                                } else {}
                            });
                            $("a.page-numbers").text()
                            $('#btn_loadmore').attr('data-current_page', data.current_page);

                        } else {

                            $(rndapf_params.shop_loop_container).find('.products').html(data.products);
                            $(rndapf_params.shop_loop_container).find('.woocommerce-pagination').html(data.pagination);
                            $(rndapf_params.shop_loop_container).find('.woocommerce-result-count').html(data.result_counts);
                            $('#btn_loadmore').remove();
                        }

                    } else {
                        if ($(rndapf_params.not_found_container).length) {
                            if (data.found) {
                                if (data.load_more) {
                                    $(rndapf_params.not_found_container).find('.products').append(data.products);
                                } else {
                                    $(rndapf_params.not_found_container).find('.products').replaceWith(data.products);
                                }
                                $(rndapf_params.not_found_container).find('.woocommerce-pagination').replaceWith(data.pagination);
                                $(rndapf_params.not_found_container).find('.woocommerce-result-count').replaceWith(data.result_counts);

                                if ($('div.rndapf-ajax-active-filter').length > 0) {
                                    $('div.rndapf-ajax-active-filter').removeClass('rndapf-widget-hidden');
                                    $('div.rndapf-ajax-active-filter').html(data.active_filter);
                                }
                                $.each($("span.page-numbers"), function(index, item) {
                                    var page = $(this).text();
                                    if (page == data.current_page) {
                                        if (parameters['query']) {
                                            url = parameters['query'];
                                        } else {
                                            url = rndapfFixPagination();
                                        }
                                        var anchor = $(this);
                                        console.log('anchor', anchor);
                                        var tt = anchor.text();
                                        var link = $('<a/>');
                                        link.attr('href', url);
                                        link.text(parseInt(tt));
                                        anchor.html(link).addClass('current');
                                        $(this).parent().siblings().removeClass("active");
                                        $(this).parent().addClass("active");
                                        $(this).addClass('current');
                                    } else {

                                    }
                                });
                                $("a.page-numbers").text()
                                $('#btn_loadmore').attr('data-current_page', data.current_page);

                            } else {

                                $(rndapf_params.not_found_container).find('.products').html(data.products);
                                $(rndapf_params.not_found_container).find('.woocommerce-pagination').html(data.pagination);
                                $(rndapf_params.not_found_container).find('.woocommerce-result-count').html(data.result_counts);
                                $('#btn_loadmore').remove();
                            }
                        }
                    }

                    // reinitialize ordering
                    // rndapfInitOrder();
                    // reinitialize dropdown filter
                    rndapfDropDownFilter();
                    // run scripts after shop loop undated
                    if (typeof rndapf_params.custom_scripts !== 'undefined' && rndapf_params.custom_scripts.length > 0) {
                        eval(rndapf_params.custom_scripts);
                    }

                }
            }
        })
        request.done(function(data) {
            $('.rndapf-before-update').remove();
        });

    }

    // URL Parser
    rndapfGetUrlVars = function(url) {
        var vars = {},
            hash;

        if (typeof url == 'undefined') {
            url = window.location.href;
        } else {
            url = url;
        }
        var hashes = url.slice(url.indexOf('?') + 1).split('&');
        for (var i = 0; i < hashes.length; i++) {
            hash = hashes[i].split('=');
            vars[hash[0]] = hash[1];
        }
        return vars;
    }

    // if current page is greater than 1 then we should set it to 1
    // everytime we add new query to url to prevent page not found error.
    rndapfFixPagination = function() {
        if ($post_data['query']) {
            var url = $post_data['query'],
                params = rndapfGetUrlVars(url);
        } else {
            var url = window.location.href,
                params = rndapfGetUrlVars(url);
        }


        if (current_page = parseInt(url.replace(/.+\/page\/([0-9]+)+/, "$1"))) {
            if (current_page > 1) {
                $post_data['paged'] = current_page;
                url = url.replace(/page\/([0-9]+)/, 'page/1');
            } else {
                $post_data['paged'] = 1;
            }
        } else if (typeof params['paged'] != 'undefined') {
            current_page = parseInt(params['paged']);
            if (current_page > 1) {
                $post_data['paged'] = current_page;
                url = url.replace('paged=' + current_page, 'paged=1');
            }
        }

        return url;
    }

    // update query string for categories, meta etc..
    rndapfUpdateQueryStringParameter = function(key, value, push_history, url) {
        if (typeof push_history === 'undefined') {
            push_history = true;
        }

        if (typeof url === 'undefined') {
            url = rndapfFixPagination();
        }

        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i"),
            separator = url.indexOf('?') !== -1 ? "&" : "?",
            url_with_query;

        if (url.match(re)) {
            url_with_query = url.replace(re, '$1' + key + "=" + value + '$2');
        } else {
            url_with_query = url + separator + key + "=" + value;
        }

        if (push_history === true) {

            $post_data['query'] = url_with_query;
            // return history.pushState({}, '', url_with_query);
            return url_with_query;
        } else {
            return url_with_query;
        }
    }

    // remove parameter from url
    rndapfRemoveQueryStringParameter = function(filter_key, url) {
        if (typeof url === 'undefined') {
            url = rndapfFixPagination();
        }

        var params = rndapfGetUrlVars(url),
            count_params = Object.keys(params).length,
            start_position = url.indexOf('?'),
            param_position = url.indexOf(filter_key),
            clean_url,
            clean_query;

        if (count_params > 1) {
            if ((param_position - start_position) > 1) {
                clean_url = url.replace('&' + filter_key + '=' + params[filter_key], '');
            } else {
                clean_url = url.replace(filter_key + '=' + params[filter_key] + '&', '');
            }

            var params = clean_url.split('?');
            clean_query = '?' + params[1];
        } else {
            clean_query = url.replace('?' + filter_key + '=' + params[filter_key], '');
        }

        return clean_query;
    }

    // add filter if not exists else remove filter
    rndapfSingleFilter = function(filter_key, filter_val) {
        var params = rndapfGetUrlVars(),
            query;

        if (typeof params[filter_key] !== 'undefined' && params[filter_key] == filter_val) {
            query = rndapfRemoveQueryStringParameter(filter_key);
        } else {
            query = rndapfUpdateQueryStringParameter(filter_key, filter_val, false);
        }

        // update url
        $post_data['query'] = query;
        // history.pushState({}, '', query);

        // filter products
        $post_data[filter_key] = filter_val;
        rndapfFilterProducts($post_data);
    }

    // take the key and value and make query
    rndapfMakeParameters = function(filter_key, filter_val, url) {
        var params,
            next_vals,
            empty_val = false;

        if (typeof url !== 'undefined') {
            params = rndapfGetUrlVars(url);
        } else {
            params = rndapfGetUrlVars();
        }

        if (typeof params[filter_key] != 'undefined') {
            var prev_vals = params[filter_key],
                prev_vals_array = prev_vals.split(',');

            if (prev_vals.length > 0) {
                var found = jQuery.inArray(filter_val, prev_vals_array);

                if (found >= 0) {
                    // Element was found, remove it.
                    prev_vals_array.splice(found, 1);

                    if (prev_vals_array.length == 0) {
                        empty_val = true;
                    }
                } else {
                    // Element was not found, add it.
                    prev_vals_array.push(filter_val);
                }

                if (prev_vals_array.length > 1) {
                    next_vals = prev_vals_array.join(',');
                } else {
                    next_vals = prev_vals_array;
                }
            } else {
                next_vals = filter_val;
            }
        } else {
            next_vals = filter_val;
        }

        // update url and query string
        if (empty_val == false) {
            rndapfUpdateQueryStringParameter(filter_key, next_vals);
            $post_data[filter_key] = next_vals;
        } else {
            var query = rndapfRemoveQueryStringParameter(filter_key);
            $post_data['query'] = query;
            // history.pushState({}, '', query);
        }



        // filter products
        rndapfFilterProducts($post_data);
    }

    // handle the filter request
    $('.rndapf-ajax-term-filter').not('.rndapf-price-filter-widget').on('click', 'li a', function(event) {
        event.preventDefault();
        var element = $(this),
            filter_key = element.attr('data-key'),
            filter_val = element.attr('data-value'),
            enable_multiple_filter = element.attr('data-multiple-filter');

        if (enable_multiple_filter == true) {
            rndapfMakeParameters(filter_key, filter_val);
            $post_data[filter_key] = filter_val;
        } else {
            $post_data[filter_key] = filter_val;
            rndapfSingleFilter(filter_key, filter_val);
        }

    });

    // handle the filter request for price filter display type list
    $('.rndapf-price-filter-widget.rndapf-ajax-term-filter').on('click', 'li a', function(event) {
        event.preventDefault();
        var element = $(this),
            filter_key_min = element.attr('data-key-min'),
            filter_val_min = element.attr('data-value-min'),
            filter_key_max = element.attr('data-key-max'),
            filter_val_max = element.attr('data-value-max'),
            query;

        if (element.parent().hasClass('chosen')) {
            query = rndapfRemoveQueryStringParameter(filter_key_min);
            query = rndapfRemoveQueryStringParameter(filter_key_max, query);

            if (query == '') {
                query = window.location.href.split('?')[0];
            }
            $post_data['query'] = query;
            // history.pushState({}, '', query);
        } else {
            query = rndapfUpdateQueryStringParameter(filter_key_min, filter_val_min, false);
            $post_data[filter_key_min] = filter_val_min;
            query = rndapfUpdateQueryStringParameter(filter_key_max, filter_val_max, true, query);
            $post_data['query'] = query;
            $post_data[filter_key_max] = filter_key_max;
        }
        // filter products
        rndapfFilterProducts($post_data);
    });

    // handle the pagination request
    if (rndapf_params.pagination_container.length > 0) {
        var holder = rndapf_params.pagination_container + ' a';

        $(document).on('click', holder, function(event) {
            event.preventDefault();
            var location = $(this).attr('href');
            $post_data['query'] = location;
            url = rndapfFixPagination();

            var anchor = $(this).parent().siblings('li:has(span)');

            var tt = anchor.text();
            $post_data['current_page'] = parseInt(tt) + 1;
            var link = $('<a/>');
            link.attr('href', url);
            link.text(tt);
            anchor.html(link);
            console.log(anchor, link);
            $(this).parent().siblings().removeClass("active");
            $(this).parent().addClass("active");

            //history.pushState({}, '', location);
            // filter products
            rndapfFilterProducts($post_data);
        });
    }
    // history back and forward request handling
    $(window).bind('popstate', function(event) {
        // filter products
        rndapfFilterProducts($post_data);
    });

    // ordering
    rndapfInitOrder = function() {
        if (typeof rndapf_params.sorting_control !== 'undefined' && rndapf_params.sorting_control.length && rndapf_params.sorting_control == true) {
            $('.rndapf-before-products').find('.woocommerce-ordering').each(function(index) {

                $(this).on('submit', function(event) {
                    event.preventDefault();
                });

                $(this).on('change', 'select.orderby', function(event) {
                    event.preventDefault();
                    var order = $(this).val(),
                        filter_key = 'orderby';
                    // change url
                    rndapfUpdateQueryStringParameter(filter_key, order);
                    $post_data[filter_key] = order;
                    // filter products
                    rndapfFilterProducts($post_data);
                });
            });
        }
    }

    // init ordering
    rndapfInitOrder();

    // remove active filters
    $(document).on('click', '.rndapf-active-filters a:not(.reset)', function(event) {
        event.preventDefault();
        var element = $(this),
            filter_key = element.attr('data-key'),
            filter_val = element.attr('data-value');
        console.log('filter_key  ', filter_key);
        if (typeof filter_val === 'undefined') {
            var query = rndapfRemoveQueryStringParameter(filter_key);
            $post_data['query'] = query;
            // history.pushState({}, '', query);
            // price slider
            if ($('#rndapf-noui-slider').length) {
                var priceSlider = document.getElementById('rndapf-noui-slider'),
                    min_val = parseInt($(priceSlider).attr('data-min')),
                    max_val = parseInt($(priceSlider).attr('data-max'));
                if (min_val && max_val) {
                    if (filter_key === 'min-price') {
                        priceSlider.noUiSlider.set([min_val, null]);
                    } else if (filter_key === 'max-price') {
                        priceSlider.noUiSlider.set([null, max_val]);
                    }
                    if ($post_data[filter_key]) {
                        delete $post_data[filter_key];
                        var query = rndapfRemoveQueryStringParameter(filter_key);
                        $post_data['query'] = query;
                        console.log('$post_data in ', $post_data);
                    }
                }
            }

            if ($('#rndapf-noui-slider1').length) {
                var priceSlider1 = document.getElementById('rndapf-noui-slider1'),
                    min_val = parseInt($(priceSlider1).attr('data-min')),
                    max_val = parseInt($(priceSlider1).attr('data-max'));

                if (min_val && max_val) {
                    if (filter_key === 'min-rating') {
                        priceSlider1.noUiSlider.set([min_val, null]);
                    } else if (filter_key === 'max-rating') {
                        priceSlider1.noUiSlider.set([null, max_val]);
                    }
                    if ($post_data[filter_key]) {
                        delete $post_data[filter_key];
                        var query = rndapfRemoveQueryStringParameter(filter_key);
                        $post_data['query'] = query;
                        console.log('$post_data in ', $post_data);

                    }
                }
            }

            // filter products
            rndapfFilterProducts($post_data);
        } else {

            element.remove();
            if ($post_data[filter_key]) {
                delete $post_data[filter_key];
                var query = rndapfRemoveQueryStringParameter(filter_key);
                $post_data['query'] = query;

            }
            // filter products
            rndapfFilterProducts($post_data);
            // rndapfMakeParameters(filter_key, filter_val);
        }

    });

    // clear all filters
    $(document).on('click', '.rndapf-active-filters a.reset', function(event) {
        event.preventDefault();
        var location = $(this).attr('data-location');
        // $post_data['query'] = location;
        // history.pushState({}, '', location);
        $('.rndapf-active-filters a').each(function(index) {

            var filter_key = $(this).attr('data-key');
            if (filter_key) {
                var query = rndapfRemoveQueryStringParameter(filter_key);
                $post_data['query'] = query;
            }
            $(this).remove();
        });
        url = rndapfFixPagination();
        // filter products
        rndapfFilterProducts($post_data);
    });


    $(document).on('click', '#btn_loadmore', function(event) {
        event.preventDefault();
        var current_page = $(this).attr('data-current_page');
        $post_data['current_page'] = current_page;
        $post_data['load_more'] = 1;
        rndapfFilterProducts($post_data);
    });

    // dispaly type dropdown
    function formatState(state) {
        var depth = $(state.element).attr('data-depth'),
            $state = $('<span class="depth depth-' + depth + '">' + state.text + '</span>');

        return $state;
    }

    rndapfDropDownFilter = function() {
        if ($('.rndapf-select2-single').length) {
            $('.rndapf-select2-single').select2({
                templateResult: formatState,
                minimumResultsForSearch: Infinity,
                allowClear: true
            });
        }

        if ($('.rndapf-select2-multiple').length) {
            $('.rndapf-select2-multiple').select2({
                templateResult: formatState,
            });
        }

        $('.select2-dropdown').css('display', 'none');
    }

    // initialize dropdown filter
    rndapfDropDownFilter();

    $(document).on('change', '.rndapf-select2', function(event) {
        event.preventDefault();
        var filter_key = $(this).attr('name'),
            filter_val = $(this).val();

        if (!filter_val) {
            var query = rndapfRemoveQueryStringParameter(filter_key);
            $post_data['query'] = query;
            // history.pushState({}, '', query);
        } else {
            filter_val = filter_val.toString();
            rndapfUpdateQueryStringParameter(filter_key, filter_val);
        }
        $post_data[filter_key] = filter_val;
        // filter products
        rndapfFilterProducts($post_data);
    });
});