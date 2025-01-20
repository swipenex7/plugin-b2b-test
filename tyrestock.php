<?php
/*
Plugin Name: Tyrestock
Description: Listino gomme del servizio Tyres Stock - Servizio in abbonamento. Richiedere Api key per attivare il servizio
Author: Altec service
*/
// Register style sheet.
add_action('wp_enqueue_scripts', 'register_plugin_styles');

/**
 * Register style sheet.
 */
function register_plugin_styles()
{
    wp_register_style('my-plugin', plugins_url('tyrestock/alt-min.css'));
    wp_enqueue_style('my-plugin');
}
// Enqueue JavaScript
function ppl_enqueue_scripts()
{
    wp_enqueue_script('ppl-ajax-script', plugin_dir_url(__FILE__) . 'ppl-script.js', array('jquery'), null, true);
    wp_localize_script('ppl-ajax-script', 'ppl_ajax_obj', array('ajax_url' => admin_url('admin-ajax.php'), 'cartRedirect' => wc_get_cart_url()));
}
add_action('wp_enqueue_scripts', 'ppl_enqueue_scripts');


// adding dynamics products
add_action('wp_ajax_nopriv_addTyreProduct',  'addTyreProduct');
add_action('wp_ajax_woocommerce_ajax_add_to_cart', 'woocommerce_ajax_add_to_cart');
add_action('wp_ajax_nopriv_woocommerce_ajax_add_to_cart', 'woocommerce_ajax_add_to_cart');
add_action('wp_ajax_addTyreProduct',  'addTyreProduct');


// Aggiungiamo un controllo per verificare che WooCommerce sia attivo
function check_woocommerce_dependency() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            ?>
            <div class="error">
                <p><?php _e('Tyrestock richiede che WooCommerce sia installato e attivo.', 'tyrestock'); ?></p>
            </div>
            <?php
        });
        return false;
    }
    return true;
}

function addTyreProduct()
{
    if (!check_woocommerce_dependency()) {
        wp_die('WooCommerce non è attivo');
    }

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    $reponse = NULL;
    $message = NULL;
    $productWcId = NULL;


    if (true) {

        $ean = $_POST['ean'];
        $Name = $_POST['titolo'];
        $Prezzo = $_POST['prezzo'];
        $immagine = $_POST['immagine'];
        $qty = $_POST['qty'];


        if (true) {
            if (isset($ean)) {



                $productWcId = getProductTyreWC($ean);
                $Product = [
                    "ean" => $ean,
                    "Name" => $Name,
                    "Qty" => $qty,
                    "price" => $Prezzo,
                    "imageThumbUrl" => $immagine
                ];



                if ($productWcId) {
                    // UPDATE

                    updateProductTyreWC($Product, $productWcId);
                    $message = "Saved";
                } else {

                    // NEW INSERT
                    $productWcId = newProductTyreWC($Product);
                    $message = "Saved";
                }
            }
        }
    }

    //var_dump($productWcId);
    //die();

    $response = [
        'message'  => $message,
        'id'       => $productWcId,
        'qty' => $_POST['qty']
    ];
    // DEBUG -> TO DO -> CHECK AVAILABLE QUANTITY




    header("Content-Type: application/json");
    echo json_encode($response);
    //Don't forget to always exit in the ajax function.
    exit();
    wp_die();
    die();
}


function newProductTyreWC($data)
{
    if (!check_woocommerce_dependency()) {
        return false;
    }

    global $wpdb;

    /*
    $percent_price = get_option('percent_price');
    $fixed_price = get_option('fixed_price');
    */
    $percent_price = 0;
    $fixed_price = 0;


    /*$priceFromServer = $data["Price"] * 1.22;
    $price = $priceFromServer;
    if ($fixed_price) {
        $price = $price + $fixed_price;
    }
    if ($percent_price) {
        $price *= (1 + $percent_price / 100);
    }*/
    // $price = round($price, 2);
    $price = round($data["price"], 2);
    $image = $data["imageThumbUrl"];



    $description = "";
    if (isset($data["Description"])) {
        $description = $data["Description"];
    }
    $objProduct = new WC_Product();
    $objProduct->set_name($data["Name"]);
    $objProduct->set_status("publish");  // can be publish,draft or any wordpress post status
    $objProduct->set_catalog_visibility('hidden'); // add the product visibility status
    $objProduct->set_description($description);
    $objProduct->set_sku($data["ean"]); //can be blank in case you don't have sku, but You can't add duplicate sku's
    $objProduct->set_price($price); // set product price
    $objProduct->set_regular_price($price); // set product regular price
    $objProduct->set_manage_stock(true); // true or false
    $objProduct->set_stock_quantity(1000/*$data["Qty"]*/);
    $objProduct->set_stock_status('instock'); // in stock or out of stock value
    $objProduct->set_backorders('no');
    $objProduct->set_reviews_allowed(false);
    $objProduct->set_sold_individually(false);
    if (isset($data["pfu"])) {
        $pfu = $data["pfu"];
        $pfu = $pfu * 1.22;
        $pfu = round($pfu, 2);
        $objProduct->update_meta_data('pfu', $pfu);
    }
    // $objProduct->update_meta_data('pfu', $pfu);

    $product_id = $objProduct->save();
    $image_id = media_sideload_image($image,  $product_id, "Product Description", 'id');
    $objProduct->set_image_id($image_id);
    $objProduct->save();

    return $product_id;
}



function updateProductTyreWC($data, $id)
{
    if (!check_woocommerce_dependency()) {
        return false;
    }

    global $wpdb;

    /*
    $percent_price = get_option('percent_price');
    $fixed_price = get_option('fixed_price');
    */
    $percent_price = 0;
    $fixed_price = 0;


    /*$priceFromServer = $data["Price"] * 1.22;
    $price = $priceFromServer;
    if ($fixed_price) {
        $price = $price + $fixed_price;
    }
    if ($percent_price) {
        $price *= (1 + $percent_price / 100);
    }*/
    // $price = round($price, 2);
    $price = round($data["price"], 2);
    $image = $data["imageThumbUrl"];



    $description = "";
    if (isset($data["Description"])) {
        $description = $data["Description"];
    }




    $objProduct = new WC_Product($id);
    $objProduct->set_name($data["Name"]);
    $objProduct->set_status("publish");  // can be publish,draft or any wordpress post status
    $objProduct->set_catalog_visibility('hidden'); // add the product visibility status
    $objProduct->set_description($description);
    $objProduct->set_sku($data["ean"]); //can be blank in case you don't have sku, but You can't add duplicate sku's
    $objProduct->set_price($price); // set product price
    $objProduct->set_regular_price($price); // set product regular price
    $objProduct->set_manage_stock(true); // true or false
    $objProduct->set_stock_quantity(1000/*$data["Qty"]*/);
    $objProduct->set_stock_status('instock'); // in stock or out of stock value
    $objProduct->set_backorders('no');
    $objProduct->set_reviews_allowed(false);
    $objProduct->set_sold_individually(false);
    if (isset($data["pfu"])) {
        $pfu = $data["pfu"];
        $pfu = $pfu * 1.22;
        $pfu = round($pfu, 2);
        $objProduct->update_meta_data('pfu', $pfu);
    }
    $image_id = media_sideload_image($image,  $id, "Product Description", 'id');
    $objProduct->set_image_id($image_id);

    // $objProduct->update_meta_data('pfu', $pfu);
    $objProduct->save();
}

function getProductTyreWC($sku)
{
    // is new product if false
    $product_id = NULL;

    global $wpdb;
    $product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku));

    return $product_id;
}


function woocommerce_ajax_add_to_cart()
{

    define('WP_DEBUG', true);
    $product_id = apply_filters('woocommerce_add_to_cart_product_id', absint($_POST['product_id']));
    $quantity = empty($_POST['quantity']) ? 1 : wc_stock_amount($_POST['quantity']);
    $variation_id = absint($_POST['variation_id']);
    $passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity);
    $product_status = get_post_status($product_id);

    if ($passed_validation && WC()->cart->add_to_cart($product_id, $quantity, $variation_id) && 'publish' === $product_status) {

        do_action('woocommerce_ajax_added_to_cart', $product_id);

        if ('yes' === get_option('woocommerce_cart_redirect_after_add')) {
            wc_add_to_cart_message(array($product_id => $quantity), true);
        }

        WC_AJAX::get_refreshed_fragments();
    } else {

        $data = array(
            'error' => true,
            'quantity' => $_POST['quantity'],
            'id' => $_POST['product_id'],
            'product_url' => apply_filters('woocommerce_cart_redirect_after_error', get_permalink($product_id), $product_id)
        );

        echo wp_send_json($data);
    }

    wp_die();
}


// Create shortcode to display the textbox and list
function ppl_display_form()
{
    ob_start();
    $api_url = 'https://gommista.demogomme.it/api/tyres';
    $response = wp_remote_get($api_url);
    if (is_wp_error($response)) {
        echo json_encode(array('error' => 'Error in API request.'));
    } else {
        $body = wp_remote_retrieve_body($response);

        $data = json_decode($body, true);

        //Displaying all the data
        $okd = $data["options"][0]['data'];
        //var_dump($okd);



        echo '<div class="floatbox"><div class="item"><div class="content">
		<label for="lbl-larghezza">Larghezza</label>
		<select name="larghezza" id="larghezza" autocomplete="off" >
        <option value=""></option>';

        foreach ($okd as $okitem) {
?>


            <option value="<?php echo $okitem; ?>"><?php echo $okitem; ?></option>


        <?php

        }
        echo '</select> </div>
  </div>';

        $okd2 = $data["options"][1]['data'];
        //var_dump($okd);
        echo '<div class="item"><div class="content">
		<label for="lbl-altezza">Altezza</label>
		<select name="altezza" id="altezza"  autocomplete="off" ><option value=""></option>';

        foreach ($okd2 as $okitem) {
        ?>


            <option value="<?php echo $okitem; ?>"><?php echo $okitem; ?></option>


        <?php

        }
        echo '</select></div>
  </div>';

        $okd3 = $data["options"][2]['data'];
        //var_dump($okd);
        echo '<div class="item"><div class="content">
		<label for="lbl-diametro">Diametro</label>
		<select name="diametro" id="diametro" autocomplete="off" >
        <option value=""></option>';

        foreach ($okd3 as $okitem) {
        ?>


            <option value="<?php echo $okitem; ?>"><?php echo $okitem; ?></option>


        <?php

        }
        echo '</select></div>
  </div>	';

        $okd4 = $data["options"][3]['data'];
        //var_dump($okd);
        echo '<div class="item"><div class="content">
		<label for="lbl-marca">Marca</label>
		<select name="marca" id="marca"  autocomplete="on" >
        <option value=""></option>';

        foreach ($okd4 as $okitem) {
        ?>


            <option value="<?php echo $okitem; ?>"><?php echo $okitem; ?></option>


        <?php

        }
        echo '</select></div>
  </div>	';

        $okd5 = $data["options"][4]['data'];
        //var_dump($okd);
        echo '<div class="item"><div class="content">
		<label for="lbl-settore">settore</label>
		<select name="settore" id="settore" class="testo_nome" autocomplete="on" >
        ';


        foreach ($okd5 as $okitem) {
        ?>


            <option value="<?php echo $okitem; ?>"><?php echo $okitem; ?></option>


    <?php

        }
        echo '</select></div>
  </div>	';
    }
    ?>
    <div class="item">
        <div class="content">
            <input type="hidden" id="ppl-product-name" value="abc">
            <button id="ppl-check-price" class="button-1" onclick="return false">Ricerca</button>

        </div>
    </div>
    </div>
    <div id="ppl-results"></div>
    <?php
    return ob_get_clean();
}
add_shortcode('listino_tyres', 'ppl_display_form');

// Handle AJAX request
function ppl_fetch_price()
{
    // var_dump($_POST);
    if (isset($_POST['product_name'])) {

        $altezzaC = sanitize_text_field($_POST['altezza_trova']);
        $larghezzaC = sanitize_text_field($_POST['larghezza_trova']);
        $diametroC = sanitize_text_field($_POST['diametro_trova']);
        $marcaC = sanitize_text_field($_POST['marca_trova']);
        $settoreC = sanitize_text_field($_POST['settore_trova']);



        // API endpoint
        $api_url = 'https://gommista.demogomme.it/api/tyres'; // Cambia questo con il tuo URL API
        $json_richiesta = '{
    "activeFilter": {
        "page": {
            "value": 1,
            "type": "page"
            "size": 100
        },
        "stagioni": {
            "value": {},
            "type": "multiple",
            "key": "gom_stagione",
            "label": "Stagione"
        },
        "quality": {
            "value": {},
            "type": "multiple",
            "key": "gom_qualita",
            "label": "Qualità"
        },
        "runflat": {
            "value": {},
            "type": "multiple",
            "key": "gom_runflat",
            "label": "Runflat"
        },
        "larghezza": {
            "value": "' . $larghezzaC . '",
            "type": "single",
            "key": "gom_larghezza",
            "label": "Larghezza"
        },
        "altezza": {
            "value": "' . $altezzaC . '",
            "type": "single",
            "key": "gom_altezza",
            "label": "Altezza"
        },
        "diametro": {
            "value": "' . $diametroC . '",
            "type": "single",
            "key": "gom_diametro",
            "label": "Diametro"
        },
        "marca": {
            "value": "' . $marcaC . '",
            "type": "single",
            "key": "mar_marca",
            "label": "Marca"
        },
        "ean": {
            "value": "",
            "type": "single",
            "key": "gom_ean",
            "label": "EAN"
        },
        "settore": {
            "value": "' . $settoreC . '",
            "type": "single",
            "key": "gom_settore",
            "label": "Settore"
        },
        "velocita": {
            "value": "",
            "type": "single",
            "key": "gom_indice_velocita",
            "label": "Indice Velocità"
        },
        "carico": {
            "value": "",
            "type": "single",
            "key": "gom_indice_carico",
            "label": "Indice Carico"
        }
    }
}';
        //echo $json_richiesta; die();
        $response = wp_remote_post($api_url, array(
            'body' => $json_richiesta,
            'headers' => array('Content-Type' => 'application/json'),
        ));

        //  $response = wp_remote_get($api_url);

        if (is_wp_error($response)) {
            echo json_encode(array('error' => 'Inserire dati per la ricerca'));
        } else {
            $body = wp_remote_retrieve_body($response);


            $datagomme = json_decode($body, true);
            //  print_r($datagomme);
            //Displaying all the data
            $okgomme = $datagomme["inertiaTyres"]["data"];
            //var_dump($okgomme);
            echo '';

            foreach ($okgomme as $okitem) {
                //var_dump($okitem);
    ?>
                <div class="card">
                    <div class="box">

                        <img src="https://api.demogomme.it/uploads/files/<?php echo $okitem['gom_foto']; ?>" class="imggomma">

                        <img src="<?php echo $okitem['mar_logo']; ?>" class="imggomma">
                    </div>
                    <div class="box">
                        <p class="descrizione"><?php echo $okitem['gom_descrizione']; ?></p>
                        <p class="gomma"><?php echo $okitem['gom_stagione'] . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src='https://api.demogomme.it/images/cons.png'  width='22'> </img>&nbsp;" . $okitem['gom_classe_energetica'] . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src='https://api.demogomme.it/images/ade.png'  width='22'> </img>&nbsp;" . $okitem['gom_aderenza_bagnato'] . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src='https://api.demogomme.it/images/rum.png' width='22'> </img>&nbsp;" . $okitem['gom_rumorosita']; ?></p>
                    </div>
                    <div class="box">
                        <p class="prezzo">€. &nbsp;<?php echo number_format($okitem['prezzo'], 2, ',', '.');
                                                    ?></p>
                        <P>
                            <button class="button-1 button-add-To-Cart" id=""
                                data-ean="<?php echo strip_tags($okitem['gom_ean']); ?>"
                                data-titolo="<?php echo strip_tags($okitem['gom_descrizione']); ?>"
                                data-stagione="<?php echo $okitem['gom_stagione']; ?>"
                                data-prezzo="<?php echo number_format($okitem['prezzo'], 2, ',', '.'); ?>"
                                data-marca="<?php echo $okitem['mar_logo']; ?>"
                                data-immagine="https://api.demogomme.it/uploads/files/<?php echo $okitem['gom_foto']; ?>"
                                data-classe_energetica="<?php echo $okitem['gom_classe_energetica']; ?>"
                                data-aderenza_bagnato="<?php echo $okitem['gom_aderenza_bagnato']; ?>"
                                data-rumorosita="<?php echo $okitem['gom_rumorosita']; ?>">Aggiungi al carrello</button>
                        </P>
                    </div>
                </div>


<?php

            }
            echo ' ';
        }
    }
    wp_die();
}
add_action('wp_ajax_ppl_fetch_price', 'ppl_fetch_price');
add_action('wp_ajax_nopriv_ppl_fetch_price', 'ppl_fetch_price');

?>