Formula Parser
==============

Formula Parser is a PHP class for parsing and derivation of mathematical formula entered as a string. It's like what you can do in R, but right on a website. This class can give to users of your site or application functionality to calculate a big formula (10000 characters max by default) and get the answer online.

Setup and Usage
---------------

Simply include this class into your project like so:

`include_once('/libraries/FormulaParser.php');`

Then invoke the class in your project using the class constructor:

`$formula = new FormulaParser($my_formula, $lang, $max_length, $characters_number);`

`$my_formula` The formula entered as a string

`$lang` Setting the language ('en', 'ru' or 'es')

`$max_length` Setting the maximum possible length of the formula

`$characters_number` Setting the number of characters after the decimal point in a calculated answer


The initialized object `$formula` has two public methods to work with the class:

`getResult()` Returns an result array(0=>value1, 1=>value2), where value1 is the operating status, which can be 'done' or 'error', and value2 is a calculated answer or error message in the language set in constructor. The successful calculated answer is a float with set number of characters after the decimal point.

`getFormula()`  Returns the initially entered formula

Example
-------

Let's see how easy this class is to use. For example, entered formula is: ((8+(10*(3+5)))/2.1)-5^2

```
$my_formula = '((8+(10*(3+5)))/2.1)-5^2';
$formula = new FormulaParser($my_formula, 'en', 10000, 4);
$result = $formula->getResult(); // return an array (0=>'done', 1=>16.9048)
if ($result[0]=='done') {
  echo "Answer: $result[1]";
} elseif ($result[0]=='error') {
  echo "Error: $result[1]";
}
```

An example of a site made in pure PHP using Formula Parser class: [www.yoursimpleformula.com](http://www.yoursimpleformula.com)

###License

This program is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
