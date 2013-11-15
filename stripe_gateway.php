<?php
require_once("lib/stripe-php/lib/Stripe.php");
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
        $this->icon 		          = WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . '/images/credits.png';
        $this->usesandboxapi      = strcmp($this->settings['debug'], 'yes') == 0;
        $this->testApiKey 		    = $this->settings['test_api_key'  ];
        $this->liveApiKey 		    = $this->settings['live_api_key'  ];
        $this->testPublishableKey = $this->settings['test_publishable_key'  ];
        $this->livePublishableKey = $this->settings['live_publishable_key'  ];
        $this->publishable_key    = $this->usesandboxapi ? $this->testPublishableKey : $this->livePublishableKey;
        $this->secret_key         = $this->usesandboxapi ? $this->testApiKey : $this->liveApiKey;
        $this->capture            = strcmp($this->settings['debug'], 'yes') == 0;

        // tell WooCommerce to save options
        add_action('woocommerce_update_options_payment_gateways_' . $this->id , array($this, 'process_admin_options'));
        add_action('admin_notices'                              , array(&$this, 'perform_ssl_check'    ));
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
        $charge = Stripe_Charge::create(array(
          "amount"      => $data['amount'], // amount in cents, again
          "currency"    => $data['currency'],
          "card"        => $data['token'],
          "description" => $data['card']['name'],
          "capture"     => !$this->capture,
        ));
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
        $this->order        = &new WC_Order($order_id);
        if ($this->send_to_stripe())
        {
          $this->completeOrder();
          return array(
            'result'   => 'success',
            'redirect' => add_query_arg(
              'order',
              $this->order->id,
              add_query_arg(
                  'key',
                  $this->order->order_key,
                  get_permalink(get_option('woocommerce_thanks_page_id'))
              )
            )
          );
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
            "currency"    => strtolower(get_option('woocommerce_currency')),
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


function striper_order_status_completed($order_id)
{
  global $woocommerce;
  $authcap = get_post_meta( $order_id, 'auth_capture', true);
  if($authcap)
  {
    Stripe::setApiKey(get_post_meta( $order_id, 'key', true));
    try
    {
      $ch = Stripe_Charge::retrieve(get_post_meta( $order_id, 'transaction_id', true));
      $ch->capture();
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

