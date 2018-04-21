<?php
namespace Geeky\CVParser;

use Illuminate\Support\Facades\Facade;

class CVParserFacade extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return CVParserController::class; }

}