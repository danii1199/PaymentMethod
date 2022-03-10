<?php
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) return;

add_action('m4p-loaded', 'sepalo_payment_init', 11);

const TOKEN = "1";
const TPVURL = "";

function sepalo_payment_init()
{
    if (class_exists('WC_Payment_Gateway')) {
        class WC_Sepalo_pay_Gateway extends WC_Payment_Gateway
        {

            public function __construct()
            {
                add_action('dc-payment_method_sepalo', array($this, 'payment_method_sepalo_action'));
                $this->id   = 'sepalo_payment';
                $this->has_fields = false;
                $this->order_button_text = __('Ir a Sepalo', 'woocommerce');
                $this->method_title = __('Pago por Sepalo ', 'sepalo-pay-woo');
                $this->method_description = __('Los clientes realizan el pago a través de Sepalo', 'sepalo-pay-woo');

                $this->title = $this->get_option('title');
                $this->description = $this->get_option('description');
                $this->payment_request      = 'yes' === $this->get_option('payment_request', 'yes');

                $this->init_form_fields();
                $this->init_settings();

                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));


                // add_action( 'woocommerce_thank_you_' . $this->id, array( $this, 'thank_you_page' ) );
            }

            public function init_form_fields()
            {
                $this->form_fields = apply_filters('woo_sepalo_pay_fields', array(
                    'enabled' => array(
                        'title' => __('Activar/Desactivar', 'sepalo-pay-woo'),
                        'type' => 'checkbox',
                        'label' => __('Activar Sepalo', 'sepalo-pay-woo'),
                        'default' => 'no'
                    ),
                    'title' => array(
                        'title' => __('Título', 'sepalo-pay-woo'),
                        'type' => 'text',
                        'default' => __('Sepalo', 'sepalo-pay-woo'),
                        'desc_tip' => true,
                        'description' => __('Esto controla el título que el usuario ve al terminar la compra.', 'sepalo-pay-woo')
                    ),
                    'description' => array(
                        'title' => __('Sepalo Payments Gateway Description', 'sepalo-pay-woo'),
                        'type' => 'textarea',
                        'default' => __('Realiza tu pago a través de Sepalo', 'sepalo-pay-woo'),
                        'desc_tip' => true,
                        'description' => __('Esto controla la descripción que el usuario verá al acabar la compra.', 'sepalo-pay-woo')
                    ),
                    'email'                 => array(
                        'title'       => __('Correo electrónico del receptor', 'sepalo-pay-woo'),
                        'type'        => 'email',
                        'description' => __('Por favor, escribe tu dirección de correo electrónico de PayPal, se necesita para recibir el pago.', 'sepalo-pay-woo'),
                        'default'     => get_option('admin_email'),
                        'desc_tip'    => true,
                        'placeholder' => 'you@youremail.com',
                    ),
                    'id_client' => array(
                        'title' => __('Id de acceso a sepalo', 'sepalo-pay-woo'),
                        'type' => 'text',
                        'default' => __('Id de cliente', 'sepalo-pay-woo'),
                        'desc_tip' => true,
                        'description' => __('Id del cliente de Sepalo', 'sepalo-pay-woo')
                    ),
                    'secret_key_client' => array(
                        'title' => __('Secret Key de Sepalo', 'sepalo-pay-woo'),
                        'type' => 'text',
                        'default' => __('', 'sepalo-pay-woo'),
                        'desc_tip' => true,
                        'description' => __('Password de conexión con Sepalo', 'sepalo-pay-woo')
                    ),
                ));
            }
            public function get_icon()
            {

                $icon_html = '<img src="https://sepalo.es/wp-content/uploads/2020/10/cropped-sepalo_soloGrafico-32x32.png" alt="Sepalo" >' . '<a style="font-size: 13px;" href="https://sepalo.es/" target="_blank">¿Qué es Sepalo?</a>';

                return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
            }


            /**
             * Process the payment and return the result.
             *
             * @param  int $order_id Order ID.
             * @return array
             */

            public function process_payment($order_id)
            {
                global $woocommerce;
                include_once dirname(__FILE__) . '/class-wc-gateway-sepalo-request.php';

                $order          = wc_get_order($order_id);

                $sepalo_request = new WC_Gateway_Sepalo_Request($this);


                $woocommerce->cart->empty_cart();
                $order->payment_complete();
                return array(
                    'result'   => 'success',
                    'redirect' => $sepalo_request->get_request_url($order),
                );
            }

            public function payment_method_sepalo_action()
            {


                if ($_SERVER['REQUEST_METHOD'] === 'GET') {

                    $get_vars = $_SERVER['REQUEST_URI'];
                    $vars = explode('?', $get_vars);
                    $args = unserialize(base64_decode($vars[1]));
                    echo '
                    <script language="Javascript">
                        function OnLoadEvent() {
                            document.form.submit();
                        }
                    </script>

                    <body OnLoad="OnLoadEvent();">
                    <form name="form" action="https://des.datta.capital/pago-con-sepalo" method="post">
                        <input type="hidden" name="id_user" value="' . $args['id_user'] . '">
                        <input type="hidden" name="first_name" value="' . $args['first_name'] . '">
                        <input type="hidden" name="last_name" value="' . $args['last_name'] . '">
                        <input type="hidden" name="email" value="' . $args['email'] . '">
                        <input type="hidden" name="amount" value="' . $args['amount'] . '">
                        <input type="hidden" name="id_order" value="' . $args['id_order'] . '">
                        <input type="hidden" name="id_client" value="' . $args['id_client'] . '">
                        <input type="hidden" name="secret_key" value="' . $args['secret_key'] . '">
                        <input type="hidden" name="order_key" value="' . $args['order_key'] . '">
                    <noscript>
                        <p>Please click</p><input id="to-asc-button" type="submit">
                    </noscript>
                    </form>
                    </body>
                    ';
                }

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $payment = $this->initPayment(floatval($_POST["amount"]));
                    if ($payment) {
                        // Redirect to payment gateway with POST
                        echo '
                <form id="PurchaseForm" method="post" action="' . $payment->{TPVURL} . '"> 
                    <input name="Token" type="hidden" value="' . $payment->{TOKEN} . '"> 
                </form>
                <script>
                    document.getElementById("PurchaseForm").submit();
                </script>
            ';
                    } else {
                        echo '<pre style="background-color: indianred;padding: 1em;">' . $this->errormessage . '</pre>';
                        $this->error = "No es posible realizar el pago en este momento. Por favor, inténtelo de nuevo más tarde.";
                    }
                }
            }
            function initPayment($amount)
            {

                $pay_date = date('d-m-Y');

                $base_url = 'https://public-uat.xilemapayments.com/estelaCore/gw/v1.0';
                $url = $base_url . '/startPayment';

                $trxDate =  DateTime::createFromFormat('d-m-Y', $pay_date);
                //La URL de callback debe ser una URL pública
                //$callbackUrl = $this->getBaseUrl() . 'callback.php';

                //Establecimiento de rutas de éxito. cancelación y error
                $successUrl = 'https://des.datta.capital/finalizar-compra/pedido-recibido/' . $_POST['id_order'] . '?key=' . $_POST['order_key'];
                $errorUrl = 'https://des.datta.capital/tienda';
                $cancelUrl = 'https://des.datta.capital/tienda';
                //$callbackUrl = $actual_link . '/callback.php';
                $data = array(
                    "merchant" => array(
                        "clientId" => $_POST['id_client'],
                        "clientSecret" => $_POST['secret_key'],
                    ),
                    "trx" => array(
                        "source" => 'eCommerce',
                        "reference" => '12345',
                        "datetime" => $trxDate->format(DateTime::ISO8601),
                        "amount" => $amount,
                        "allow3DSecure" => true,
                        "currency" => array(
                            "numCode" => 978,
                            "alphaCode" => "EUR"
                        )
                    ),
                    "additionals" => array(
                        "customer" => array(
                            "id" => $_POST['id_user'],
                            "name" => $_POST['first_name'],
                            "lastName" => $_POST['last_name'],
                            "email" => $_POST['email']
                        ),
                        "configuration" => array(
                            "styleName" => '',
                            "language" => 'es',
                            //"callbackUrl" => $callbackUrl,
                            "successUrl" => $successUrl,
                            "errorUrl" => $errorUrl,
                            "cancelUrl" => $cancelUrl
                        )
                    )
                );

                $options = array(
                    "http" => array(
                        "header"  => "Content-Type: application/json",
                        "method"  => "POST",
                        "content" => json_encode($data)
                    ),
                    "ssl" => array(
                        "verify_peer" => false,
                        "verify_peer_name" => false
                    )
                );

                //print_r($data);
                json_encode($data);
                $context  = stream_context_create($options);
                try {

                    $result = @file_get_contents($url, false, $context);
                    //print_r($result);
                } catch (Exception $e) {
                    return FALSE;
                }

                if ($result === FALSE) {
                    $error0 = error_get_last();

                    $this->errormessage = 'ERROR-014: Petición HTTP de inicialización con errores:' . $error0['message'];
                    return FALSE;
                }
                $response = json_decode($result, true);

                if (
                    isset($response['token']) && $response['token'] != '' &&
                    isset($response['tpvUrl']) && $response['tpvUrl'] != '' &&
                    !isset($response['errors'])
                ) {
                    return (object) array(
                        TOKEN => $response['token'],
                        TPVURL => $response['tpvUrl']
                    );
                } else {
                    // Generamos error
                    $this->errormessage = 'ERROR-010: Inicialización con errores:';
                    return FALSE;
                }
            }
        }
        add_filter('woocommerce_payment_gateways', 'add_to_woo_sepalo_payment_gateway');

        function add_to_woo_sepalo_payment_gateway($gateways)
        {
            $gateways[] = 'WC_Sepalo_pay_Gateway';
            return $gateways;
        }


        /*
        function add_checkout_script()
        { ?>

            <script type="text/javascript">
                jQuery(document).on("updated_checkout", function() {
                    if ($('input[name=payment_method]:checked', '.checkout').val() == "sepalo_payment") {
                        $('.checkout').attr('action', 'https://www.google.es/');
                    } else {
                        $('.checkout').attr('action', 'https://des.datta.capital/finalizar-compra/');
                    }
                });

                $(document).on('change', '.checkout', function() {
                    if ($('input[name=payment_method]:checked', '.checkout').val() == 'sepalo_payment') {
                        $('.checkout').attr('action', 'https://www.google.es/');
                    } else {
                        $('.checkout').attr('action', 'https://des.datta.capital/finalizar-compra/');
                    }
                });
            </script>

<?php
        }
        add_action('woocommerce_after_checkout_form', 'add_checkout_script');*/
    }
}
