<?php
// Finta API Stripe per le prove locali: risponde come /v1/checkout/sessions.
$id = 'cs_test_' . bin2hex(random_bytes(6));
header('Content-Type: application/json');
echo json_encode(['id' => $id, 'url' => "https://checkout.stripe.com/c/pay/$id"]);
