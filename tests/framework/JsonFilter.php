<?php

namespace tests\framework;

/**
 * Description of JsonFilter
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class JsonFilter extends \dee\base\Filter
{
    public function after($output)
    {
        return json_encode($output);
    }
}
