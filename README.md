[🎬](https://youtu.be/FOLRATK4pVA)

In this livestream, I answered a request from one of our users to show how to take variable amount donations with Stripe Checkout using php. We use the `price_data` attribute to create Price objects on the fly.

We show how to use metadata, accept different currencies, and talked a bit about associating an existing Stripe customer object with new Checkout Sessions.


Here's the interesting bits!

```php
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

```
