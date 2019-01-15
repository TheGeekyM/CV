<?php namespace Geeky\CV;

use Illuminate\Support\Facades\Facade;

class ParserFacade extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return Parser::class; }

}
