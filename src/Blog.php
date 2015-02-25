<?php

namespace Borfast\Socializr;

class Blog
{
    public $id = '';
    public $title = '';
    public $link = '';
    public $posts = 0;
    public $name = '';
    public $updated;
    public $description = '';
    public $ask = false;
    public $ask_anon;
    public $followers = 0;

    /**
     * Create a new Blog object based on an array of attributes and a mapping
     * from those attributes to the Blog object's attributes.
     *
     * The keys are the name of the Blog object attributes, while the values
     * are the key of that attribute in the $attributes array. Like so:
     * ['blog_object_attribute' => 'key_in_attributes_array']
     *
     * @param array $mapping
     * @param array $attributes
     * @return static
     */
    public static function create(array $mapping, array $attributes)
    {
        $blog = new Blog;

        array_walk($mapping, function (&$name, $key) use (&$blog, &$attributes) {
            $blog->$key = (isset($attributes[$name])) ? $attributes[$name] : null;
        });

        return $blog;
    }
}
