<?php
if (!class_exists('Stripe')) {
    require_once("lib/stripe-php/lib/Stripe.php");
}
/*
 * Title   : Stripe Payment extension for WooCommerce
 * Author  : Sean Voss
 * Url     : http://seanvoss.com/woostriper
 * License : http://seanvoss.com/woostriper/legal
 */

class Striper extends WC_Payment_Gateway
{
    protected $GATEWAY_NAME               = "Striper";
    protected $usesandboxapi              = true;
    protected $order                      = null;
    protected $transactionId              = null;
    protected $transactionErrorMessage    = null;
    protected $stripeTestApiKey           = '';
    protected $stripeLiveApiKey           = '';
    protected $publishable_key            = '';

    public function __construct()
    {
        $this->id              = 'Striper';
        $this->has_fields      = true;

        $this->init_form_fields();
        $this->init_settings();

        $this->title              = $this->settings['title'];
        $this->description        = '';
        $this->icon 		      = $this->settings['alternate_imageurl'] ? $this->settings['alternate_imageurl']  : WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . '/images/credits.png';
        $this->usesandboxapi      = strcmp($this->settings['debug'], 'yes') == 0;
        $this->testApiKey 		  = $this->settings['test_api_key'  ];
        $this->liveApiKey 		  = $this->settings['live_api_key'  ];
        $this->testPublishableKey = $this->settings['test_publishable_key'  ];
        $this->livePublishableKey = $this->settings['live_publishable_key'  ];
        $this->useUniquePaymentProfile = strcmp($this->settings['enable_unique_profile'], 'yes') == 0;
        $this->useInterval        = strcmp($this->settings['enable_interval'], 'yes') == 0;
        $this->publishable_key    = $this->usesandboxapi ? $this->testPublishableKey : $this->livePublishableKey;
        $this->secret_key         = $this->usesandboxapi ? $this->testApiKey : $this->liveApiKey;
        $this->capture            = strcmp($this->settings['capture'], 'yes') == 0;

        // tell WooCommerce to save options
        add_action('woocommerce_update_options_payment_gateways_' . $this->id , array($this, 'process_admin_options'));
        add_action('admin_notices'                              , array(&$this, 'perform_ssl_check'    ));
        if($this->useInterval)
        {
            wp_enqueue_script('the_striper_js', plugins_url('/striper.js',__FILE__) );
        }
        wp_enqueue_script('the_stripe_js', 'https://js.stripe.com/v2/' );

    }

    public function perform_ssl_check()
    {
         if (!$this->usesandboxapi && get_option('woocommerce_force_ssl_checkout') == 'no' && $this->enabled == 'yes') :
            echo '<div class="error"><p>'.sprintf(__('%s sandbox testing is disabled and can performe live transactions but the <a href="%s">force SSL option</a> is disabled; your checkout is not secure! Please enable SSL and ensure your server has a valid SSL certificate.', 'woothemes'), $this->GATEWAY_NAME, admin_url('admin.php?page=settings')).'</p></div>';
         endif;
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'type'        => 'checkbox',
                'title'       => __('Enable/Disable', 'woothemes'),
                'label'       => __('Enable Credit Card Payment', 'woothemes'),
                'default'     => 'yes'
            ),
            'capture' => array(
                'type'        => 'checkbox',
                'title'       => __('Auth & Capture', 'woothemes'),
                'label'       => __('Enable Authorization & Capture', 'woothemes'),
                'default'     => 'no'
            ),
            'debug' => array(
                'type'        => 'checkbox',
                'title'       => __('Testing', 'woothemes'),
                'label'       => __('Turn on testing', 'woothemes'),
                'default'     => 'no'
            ),
            'title' => array(
                'type'        => 'text',
                'title'       => __('Title', 'woothemes'),
                'description' => __('This controls the title which the user sees during checkout.', 'woothemes'),
                'default'     => __('Credit Card Payment', 'woothemes')
            ),
            'test_api_key' => array(
                'type'        => 'text',
                'title'       => __('Stripe API Test Secret key', 'woothemes'),
                'default'     => __('', 'woothemes')
            ),
            'test_publishable_key' => array(
                'type'        => 'text',
                'title'       => __('Stripe API Test Publishable key', 'woothemes'),
                'default'     => __('', 'woothemes')
            ),
            'live_api_key' => array(
                'type'        => 'text',
                'title'       => __('Stripe API Live Secret key', 'woothemes'),
                'default'     => __('', 'woothemes')
            ),
            'live_publishable_key' => array(
                'type'        => 'text',
                'title'       => __('Stripe API Live Publishable key', 'woothemes'),
                'default'     => __('', 'woothemes')
            ),
            'alternate_imageurl' => array(
                'type'        => 'text',
                'title'       => __('Alternate Image to display on checkout, use fullly qualified url, served via https', 'woothemes'),
                'default'     => __('', 'woothemes')
            ),
            'enable_interval' => array(
                'type'        => 'checkbox',
                'title'       => __('Enable Interval', 'woothemes'),
                'label'       => __('Use this only if nothing else is working', 'woothemes'),
                'default'     => 'no'
            ),
            'enable_unique_profile' => array(
                'type'        => 'checkbox',
                'title'       => __('Enable Payment Profile Creation', 'woothemes'),
                'label'       => __('Use this to always create a Payment Profile in Stripe (always creates new profile, regardless of logged in user), and associate the charge with the profile. This allows you more easily identify order, credit, or even make an additional charge (from Stripe admin) at a later date.', 'woothemes'),
                'default'     => 'no'
            ),


       );
    }

    public function admin_options()
    {
        include_once('templates/admin.php');
    }

    public function payment_fields()
    {
        include_once('templates/payment.php');
    }

    protected function send_to_stripe()
    {
      global $woocommerce;

      // Set your secret key: remember to change this to your live secret key in production
      // See your keys here https://manage.stripe.com/account
      Stripe::setApiKey($this->secret_key);

      // Get the credit card details submitted by the form
      $data = $this->getRequestData();

      // Create the charge on Stripe's servers - this will charge the user's card
      try {

            if($this->useUniquePaymentProfile)
            {
              // Create the user as a customer on Stripe servers
              $customer = Stripe_Customer::create(array(
                "email" => $data['card']['billing_email'],
                "description" => $data['card']['name'],
                "card"  => $data['token']
              ));
              // Create the charge on Stripe's servers - this will charge the user's card

            $charge = Stripe_Charge::create(array(
              "amount"      => $data['amount'], // amount in cents, again
              "currency"    => $data['currency'],
              "card"        => $customer->default_card,
              "description" => $data['card']['name'],
              "customer"    => $customer->id,
              "capture"     => !$this->capture,
            ));
          } else {

            $charge = Stripe_Charge::create(array(
              "amount"      => $data['amount'], // amount in cents, again
              "currency"    => $data['currency'],
              "card"        => $data['token'],
              "description" => $data['card']['name'],
              "capture"     => !$this->capture,
            ));
        }
        $this->transactionId = $charge['id'];

        //Save data for the "Capture"
        update_post_meta( $this->order->id, 'transaction_id', $this->transactionId);
        update_post_meta( $this->order->id, 'key', $this->secret_key);
        update_post_meta( $this->order->id, 'auth_capture', $this->capture);
        return true;

      } catch(Stripe_Error $e) {
        // The card has been declined, or other error
        $body = $e->getJsonBody();
        $err  = $body['error'];
        error_log('Stripe Error:' . $err['message'] . "\n");
        $woocommerce->add_error(__('Payment error:', 'woothemes') . $err['message']);
        return false;
      }
    }

    public function process_payment($order_id)
    {
        global $woocommerce;
        $this->order        = new WC_Order($order_id);
        if ($this->send_to_stripe())
        {
          $this->completeOrder();

            $result = array(
                'result' => 'success',
                'redirect' => $this->get_return_url($this->order)
            );
          return $result;
        }
        else
        {
          $this->markAsFailedPayment();
          $woocommerce->add_error(__('Transaction Error: Could not complete your payment'), 'woothemes');
        }
    }

    protected function markAsFailedPayment()
    {
        $this->order->add_order_note(
            sprintf(
                "%s Credit Card Payment Failed with message: '%s'",
                $this->GATEWAY_NAME,
                $this->transactionErrorMessage
            )
        );
    }

    protected function completeOrder()
    {
        global $woocommerce;

        if ($this->order->status == 'completed')
            return;

        $this->order->payment_complete();
        $woocommerce->cart->empty_cart();

        $this->order->add_order_note(
            sprintf(
                "%s payment completed with Transaction Id of '%s'",
                $this->GATEWAY_NAME,
                $this->transactionId
            )
        );

        unset($_SESSION['order_awaiting_payment']);
    }


  protected function getRequestData()
  {
    if ($this->order AND $this->order != null)
    {
        return array(
            "amount"      => (float)$this->order->get_total() * 100,
            "currency"    => strtolower(get_woocommerce_currency()),
            "token"       => $_POST['stripeToken'],
            "description" => sprintf("Charge for %s", $this->order->billing_email),
            "card"        => array(
                "name"            => sprintf("%s %s", $this->order->billing_first_name, $this->order->billing_last_name),
                "address_line1"   => $this->order->billing_address_1,
                "address_line2"   => $this->order->billing_address_2,
                "address_zip"     => $this->order->billing_postcode,
                "address_state"   => $this->order->billing_state,
                "address_country" => $this->order->billing_country
            )
        );
    }
    return false;
  }

}

//add_action('wp_ajax_capture_striper'     ,  'striper_order_status_completed');

function striper_order_status_completed($order_id = null)
{
  global $woocommerce;
  if (!$order_id)
      $order_id = $_POST['order_id'];

  $data = get_post_meta( $order_id );
  $total = $data['_order_total'][0] * 100;

  $params = array();
  if(isset($_POST['amount']) && $amount = $_POST['amount'])
  {
    $params['amount'] = round($amount);
  }

  $authcap = get_post_meta( $order_id, 'auth_capture', true);
  if($authcap)
  {
    Stripe::setApiKey(get_post_meta( $order_id, 'key', true));
    try
    {
      $tid = get_post_meta( $order_id, 'transaction_id', true);
      $ch = Stripe_Charge::retrieve($tid);
      if($total < $ch->amount)
      {
          $params['amount'] = $total;
      }
      $ch->capture($params);
    }
    catch(Stripe_Error $e)
    {
      // There was an error
      $body = $e->getJsonBody();
      $err  = $body['error'];
      error_log('Stripe Error:' . $err['message'] . "\n");
      $woocommerce->add_error(__('Payment error:', 'woothemes') . $err['message']);
      return null;
    }
   return true;
  }
}



function striper_add_creditcard_gateway($methods)
{
    array_push($methods, 'Striper');
    return $methods;
}

add_filter('woocommerce_payment_gateways',                      'striper_add_creditcard_gateway');
add_action('woocommerce_order_status_processing_to_completed',  'striper_order_status_completed' );

