<?php

namespace Borfast\Socializr;

class Page
{
    public $id;
    public $name;
    public $picture;
    public $link;
    public $access_token;
    public $can_post = false;

    public $provider;
    public $raw_response;


    /**
     * Create a new Page object based on an array of attributes and a mapping
     * from those attributes to the Profile object's attributes.
     * The $mapping array should have this format (example for Facebook Page):
     * $mapping = [
     *       'id' => 'id',
     *       'email' => 'email',
     *       'name' => 'name',
     *       'first_name' => 'first_name',
     *       'middle_name' => 'middle_name',
     *       'last_name' => 'last_name',
     *       'username' => 'username',
     *       'link' => 'link'
     *   ];
     * The keys are the name of the Page object attributes, while the values
     * are the key of that attribute in the $attributes array. Like so:
     * ['page_object_attribute' => 'key_in_attributes_array']
     *
     * @author Raúl Santos
     */
    public static function create(array $mapping, array $attributes)
    {
        $page = new Page;

        foreach ($mapping as $key => $name) {
            $page->$key = (isset($attributes[$name])) ? $attributes[$name] : null;
        }

        return $page;
    }
}
