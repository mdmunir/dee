<?php

namespace dee\base;

/**
 * Description of Filter
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
abstract class Filter
{
    public function before()
    {
        return true;
    }

    public function after($output)
    {
        return $output;
    }
}
