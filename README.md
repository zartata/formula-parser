Formula Parser
==============

Formula Parser is a PHP class for parsing of mathematical formula entered as a string. It's like what you can do in R, but right on a website. This class can give to users of your site or application functionality to calculate a big formula (10000 characters max by default) and get the answer online.

Supported:
* operators +, -, *, /, ^
* floating point numbers
* unlimited nested parentheses
* validation and multilingual responses

Setup and Usage
---------------

Simply include this class into your project like so:

`include_once('/libraries/FormulaParser.php');`

Then invoke the class in your project using the class constructor:

`$formula = new FormulaParser($input_string, $lang, $max_length, $characters_number);`

`$input_string` The formula entered as a string

`$lang` Setting the language ('en', 'ru' or 'es')

`$max_length` Setting the maximum possible length of the formula

`$characters_number` Setting the maximum number of characters after the decimal point in a calculated answer


The initialized object `$formula` has two public methods:

`getResult()` Returns an array(0=>value1, 1=>value2), where value1 is the operating status, which can be 'done' or 'error', and value2 is a calculated answer or error message in the set language. The successful calculated answer is a float with set maximum number of characters after the decimal point.

`getFormula()`  Returns the initially entered formula

Example
-------

Let's see how easy this class is to use. For example, user's formula is: ((8+(10*(3+5)))/2.1)-5^2

```
$input_string = '((8+(10*(3+5)))/2.1)-5^2';
$formula = new FormulaParser($input_string, 'en', 10000, 4);
$result = $formula->getResult(); // will return an array(0=>'done', 1=>16.9048)
if ($result[0]=='done') {
  echo "Answer: $result[1]";
} elseif ($result[0]=='error') {
  echo "Error: $result[1]";
}
```

An example of a site made using Formula Parser class: [www.yoursimpleformula.com](http://www.yoursimpleformula.com)

###License

This program is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
