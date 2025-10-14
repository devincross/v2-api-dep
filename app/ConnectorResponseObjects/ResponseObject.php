<?php

namespace App\ConnectorResponseObjects;

class ResponseObject implements ResponseObjectInterface, \JsonSerializable
{
    protected $fillable = [];
    protected $gaurded = [];
    protected $hidden = [];
    protected $encrypted = [];
    protected $attributes = [];
    protected $isDirty = [];
    protected $primary_key = "id";
    protected $created_at = 'created_at';
    protected $updated_at = 'updated_at';

    public function __construct($data = null) {
        $this->fill($data);
    }

    public function fill(array $data) {
        if(is_array($data)) {
            foreach($data as $key=>$value) {
                if(in_array($key, $this->fillable) && !in_array($key, $this->gaurded)) {
                    $this->attributes[$key] = $value;
                    $this->isDirty[$key] = true;
                }
            }
        }
        //maybe throw exception?
    }

    public function __get(string $key) {
        if(isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }
        return "";
    }

    public function __set(string $key, $value) {
        $this->attributes[$key] = $value;
        $this->isDirty[$key] = true;
    }

    public function has(string $key) {
        if(isset($this->attributes[$key])) {
            return true;
        }
        return false;
    }

    public function hasChanges()
    {
        return count($this->isDirty) > 0;
    }

    public function toArray()
    {
        $resp = [];
        foreach($this->attributes as $key=>$value) {
            if(!in_array($key, $this->hidden)) {
                $resp[$key] = $value;
            }
        }
        return $resp;
    }

    public function toJson()
    {
        $resp = $this->toArray();
        return json_encode($resp);
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function getId()
    {
        $this->__get($this->primary_key);
    }

    public function getCreatedAt()
    {
        $this->__get($this->created_at);
    }

    public function getUpdatedAt()
    {
        $this->__get($this->updated_at);
    }
}
