<?php

namespace App\Service;

use MongoDB\Client;

class MongoDbService
{
    private Client $client;
    
    public function __construct()
    {
        $this->client = new Client('mongodb://localhost:27017');
    }
    
    public function getClient(): Client
    {
        return $this->client;
    }
}