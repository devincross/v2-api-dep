<?php

namespace App\ConnectorResponseObjects;

interface ResponseObjectInterface
{
    public function getId();
    public function getCreatedAt();
    public function getUpdatedAt();
    public function fill(array $data);
    public function has(string $key);
    public function hasChanges();
    public function toArray();
    public function toJson();
}
