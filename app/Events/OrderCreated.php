<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated
{
    use Dispatchable, SerializesModels;

    public $userId;
    public $orderData;

    public function __construct($userId, $orderData)
    {
        $this->userId = $userId;
        $this->orderData = $orderData;
    }
}
