<?php
namespace App\Models\Traits;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

trait Encryptable
{
    /**
     * If the attribute is in the encryptable array
     * then decrypt it.
     *
     * @param  $key
     *
     * @return $value
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (in_array($key, $this->encryptable)) {
            try {
                $value = Crypt::decrypt($value);
            } catch (DecryptException $e) {
                //ignore decryption errors - most likely an empty value
            }
        }
        return $value;
    }
    /**
     * If the attribute is in the encryptable array
     * then encrypt it.
     *
     * @param $key
     * @param $value
     */
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->encryptable)) {
            $value = Crypt::encrypt($value);
        }
        return parent::setAttribute($key, $value);
    }

    /**
     * When need to make sure that we iterate through
     * all the keys.
     *
     * @return array
     */
    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();
        foreach ($this->encryptable as $key) {
            if (isset($attributes[$key]) && $attributes[$key] !== '' && !is_null($attributes[$key])) {
                $attributes[$key] = Crypt::decrypt($attributes[$key]);
            }
        }
        return $attributes;
    }
}
