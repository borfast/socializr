<?php

namespace Borfast\Socializr;

class Profile
{
    public $provider;
    public $id;
    public $email;
    public $name;
    public $first_name;
    public $middle_name;
    public $last_name;
    public $username;
    public $link;
    public $raw_response;
    public $avatar;


    /**
     * Create a new Profile object based on an array of attributes and a mapping
     * from those attributes to the Profile object's attributes.
     * The $mapping array should have this format (example for Facebook):
     *
     * $mapping = [
     *     'id' => 'id',
     *     'email' => 'email',
     *     'name' => 'name',
     *     'first_name' => 'first_name',
     *     'middle_name' => 'middle_name',
     *     'last_name' => 'last_name',
     *     'username' => 'username',
     *     'link' => 'link'
     * ];
     *
     * The keys are the name of the Profile object attributes, while the values
     * are the key of that attribute in the $attributes array. Like so:
     * ['profile_object_attribute' => 'key_in_attributes_array']
     *
     * @param array $mapping
     * @param array $attributes
     * @return static
     */
    public static function create(array $mapping, array $attributes)
    {
        $profile = new Profile;

        array_walk($mapping, function (&$name, $key) use (&$profile, &$attributes) {
            $profile->$key = (isset($attributes[$name])) ? $attributes[$name] : null;
        });

        return $profile;
    }
}
