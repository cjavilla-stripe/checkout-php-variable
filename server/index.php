<?php
use Slim\Http\Request;
use Slim\Http\Response;
use Stripe\Stripe;

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();

require './config.php';

$app = new \Slim\App;

$app->add(function ($request, $response, $next) {
    Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));
    return $next($request, $response);
});

$app->get('/', function (Request $request, Response $response, array $args) {
  return $response->write(file_get_contents(getenv('STATIC_DIR') . '/index.html'));
});

$app->post('/checkout_sessions', function(Request $request, Response $response) use ($app)  {
  $params = json_decode($request->getBody());
  $payment_method_types = [
    'usd' => ['card', 'alipay'],
    'eur' => ['card', 'ideal', 'giropay'],
    'cad' => ['card']
  ];
  $products = [
    'cause-a' => 'prod_I2VveZkGp0oSVR',
    'cause-b' => 'prod_I2VwYnNVr3zLpN',
  ];

  $session = \Stripe\Checkout\Session::create([
    'success_url' => 'http://localhost:4242/?success=true',
    'cancel_url' => 'http://localhost:4242/?cancel=true',
    'mode' => 'payment',
    'customer' => 'cus_I2WFt65ZiwwvIE',
    'payment_method_types' => $payment_method_types[$params->currency],
    'metadata' => [
      'cause' => $params->cause,
    ],
    'submit_type' => 'donate',
    'line_items' => [[
      'price_data' => [
        'currency' => $params->currency,
        'product' => $products[$params->cause],
        'unit_amount' => $params->amount,
      ],
      'quantity' => 1,
    ]]
  ]);

  return $response->withJson([
    'id' => $session->id
  ]);
});

$app->post('/webhook', function(Request $request, Response $response) {
    $params = json_decode($request->getBody(), true);
    $event = \Stripe\Event::constructFrom($params);
    switch($event->type) {
      case 'checkout.session.completed':
        $session = $event->data->object;
        ob_start();
        var_dump('Checkout session completed!' . $session->id);
        error_log(ob_get_clean(), 4);
        break;
    }

    return $response->withJson([ 'status' => 'success' ])->withStatus(200);
});

$app->run();
