<?php

namespace Wramirez83\Sjwt;

use App\Models\User;

/* A class that is used to authenticate users. */
class UserAuth
{
    private static $instance;
    /**
     * > The function checks if the instance of the class is not an instance of itself, if it's not, it
     * creates a new instance of itself and returns it
     *
     * @return The user() method is being returned.
     */
    public static function user()
    {
        if (! self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * It takes an array of key/value pairs and sets the object's attributes to the values
     *
     * @param data The data to be set.
     */
    public function setAtt($data)
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * It returns the object itself.
     *
     * @return The object itself.
     */
    public function getAtt()
    {
        return $this;
    }

    public function refresh(){
        $data = User::whereId($this->id)->get()->toArray();
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    
}
