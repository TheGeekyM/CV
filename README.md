cv-parser
===================

This package parses CV via daxtra then gives you a structured data.

## Installation

Install using composer:
    
     composer require geeky/cvparser

Then, in `config/app.php`, add the following to the service providers array.

    array(
       ...
        Geeky\CV\ParserServiceProvider::class,
    )
    
Finally, in `config/app.php`, add the following to the facades array.

    array(
        'CV' => Geeky\CV\ParserFacade::class,
    )

## Usage

Example usage using Facade:
    
    $response = CV::parse('resume_url');
    



