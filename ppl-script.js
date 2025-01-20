jQuery(document).ready(function ($) {




    $(document).on('click', '.button-add-To-Cart', function (e) {
        e.preventDefault();
        var ean = $(this).data('ean');
        var titolo = $(this).data('titolo');
        var stagione = $(this).data('stagione');
        var prezzo = $(this).data('prezzo');
       
        var marca = $(this).data('marca');
        var immagine = $(this).data('immagine');
        addToCart(ean, titolo, prezzo, marca, immagine, stagione);
    });




    function addToCart(ean, titolo, prezzo, immagine_marca, immagine, stagione) {



        // loader.style.display = "block";
        let idToAddCart = ean;
        let qtyAddToCart = 4;
        if (!isNaN(idToAddCart) && !isNaN(qtyAddToCart)) {

            prezzo = parseFloat(prezzo.replace(',', '.'));
            

            jQuery.ajax({
                url: ppl_ajax_obj.ajax_url,
                type: 'post',
                // cache: false,
                redirect: 'follow',
                //    xhrFields: {
                //      withCredentials: true
                //  },
                data: {
                    action: 'addTyreProduct',
                    ean: idToAddCart,
                    titolo: titolo,
                    prezzo: prezzo,
                    immagine: immagine,
                    qty: qtyAddToCart
                },
                success: function (response) {
                    // alert(configuratore.debug);


                       console.log(response);
                   




                    var product_id = response.id;
                    var product_qty = response.qty;
                    var variation_id = 0;


                    var $thisbutton = $(this);

                    /*
               $form = $thisbutton.closest('form.cart'),
               id = $thisbutton.val(),
               product_qty = $form.find('input[name=quantity]').val() || 1,
               product_id = $form.find('input[name=product_id]').val() || id,
               variation_id = $form.find('input[name=variation_id]').val() || 0;

           */

                    var data = {
                        action: 'woocommerce_ajax_add_to_cart',
                        product_id: product_id,
                        product_sku: '', // potrei anche mandare lo sku
                        quantity: product_qty,
                        variation_id: variation_id,
                    };

                    $(document.body).trigger('adding_to_cart', [$thisbutton, data]);

                    // 

                    jQuery.ajax({
                        type: 'post',
                        url: wc_add_to_cart_params.ajax_url,
                        data: data,
                        beforeSend: function (response) {
                            $thisbutton.removeClass('added').addClass('loading');


                        },
                        complete: function (response) {
                            $thisbutton.addClass('added').removeClass('loading');
                        },
                        success: function (response) {




                            if (response.error & response.product_url) {
                                window.location = response.product_url;
                                return;
                            } else {
                                var fragments = response.fragments;

                                if (fragments) {

                                    jQuery.each(fragments, function (key, value) {
                                        jQuery(key).replaceWith(value);
                                    });

                                    // only def
                                    reisen_update_cart();

                                    function reisen_update_cart() {
                                        // Update amount on the cart button
                                        var total = jQuery('.widget_shopping_cart').eq(0).find('.total .amount').text();
                                        if (total != undefined) {
                                            jQuery('.top_panel_cart_button .cart_summa').text(total);
                                        }
                                        // Update count items on the cart button
                                        var cnt = 0;
                                        jQuery('.widget_shopping_cart_content').eq(0).find('.cart_list li').each(function () {
                                            var q = jQuery(this).find('.quantity').html().split(' ', 2);
                                            if (!isNaN(q[0]))
                                                cnt += Number(q[0]);
                                        });
                                        var items = jQuery('.top_panel_cart_button .cart_items').eq(0).text().split(' ', 2);
                                        items[0] = cnt;
                                        jQuery('.top_panel_cart_button .cart_items').text(items[0] + ' ' + items[1]);
                                        // Update data-attr on button
                                        jQuery('.top_panel_cart_button').data({
                                            'items': cnt ? cnt : 0,
                                            'summa': total ? total : 0
                                        });
                                    }

                                }

                                // $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $thisbutton]);

                            }


                            if (true) {
                                window.location = ppl_ajax_obj.cartRedirect;
                            } else {
                                $("#loader_preventivo").hide();
                            }

                           // loader.style.display = "none";
                        },
                        error: function () {
                          //  loader.style.display = "none";
                        }

                    });









                    // invece qui metto un redirect verso il prodotto se non sono in debug mode.

                },
                error: function () {
                   // loader.style.display = "none";
                }
            });






        }

    }







    $('#ppl-check-price').click(function () {
        var productName = $('#ppl-product-name').val();
        var larghezzaCerca = $('#larghezza').val();
        var altezzaCerca = $('#altezza').val();
        var diametroCerca = $('#diametro').val();
        var marcaCerca = $('#marca').val();
        var settoreCerca = $('#settore').val();

        $.ajax({
            url: ppl_ajax_obj.ajax_url,
            method: 'POST',
            data: {
                action: 'ppl_fetch_price',
                product_name: productName,
                larghezza_trova: larghezzaCerca,
                altezza_trova: altezzaCerca,
                diametro_trova: diametroCerca,
                marca_trova: marcaCerca,
                settore_trova: settoreCerca
            },
            success: function (response) {
                $('#ppl-results').html(response);


            },
            error: function () {
                $('#ppl-results').html('Error retrieving the price list.');
            }
        });
    });
});
