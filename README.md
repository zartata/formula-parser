Formula Parser
==============

Formula Parser is a PHP class for parsing and evaluating mathematical formula entered as a string.

Key features:
* Uses Float for calculation and result
* Precision rounding can be set
* Unlimited nested parentheses
* Makes validation and multilingual responses
 
Supported:
* Operators +, -, *, /, ^
* Numbers with decimal point '.'
* Numbers in scientific E notation (e0, e+, e-)
* Constants: pi

Setup and Usage
---------------

Simply include this class into your project like so:

`include_once('/libraries/FormulaParser.php');`

Then invoke the class in your project using the class constructor:

`$formula = new FormulaParser($input_string, $language, $precision_rounding);`

`$input_string` The formula entered as a string

`$language` Setting the language ('en', 'ru' or 'es')

`$precision_rounding` Setting the maximum number of characters after the decimal point in a calculated answer


The initialized object `$formula` has two public methods:

`getResult()` Returns an array(0=>value1, 1=>value2), where value1 is the operating status, which can be 'done' or 'error', and value2 is a calculated answer or error message in the set language.

`getFormula()`  Returns the text of the formula passed to the constructor

Example
-------

The following example shows how easy this class is to use. For instance, user's formula is: ((8+(10*(3+5)))/2.1)-5^2

```
$formula = new FormulaParser('((8+(10*(3+5)))/2.1)-5^2', 'en', 4);
$result = $formula->getResult(); // will return an array(0=>'done', 1=>16.9048)
if ($result[0]=='done') {
  echo "Answer: $result[1]";
} elseif ($result[0]=='error') {
  echo "Error: $result[1]";
}
```

More examples can be found on [www.yoursimpleformula.com](http://www.yoursimpleformula.com) - the web application made using Formula Parser class.

###License

This program is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
