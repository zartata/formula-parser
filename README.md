Formula Parser
==============

Formula Parser is a class for parsing mathematical formulas from a string. It's like what you can do in R, but right on a website. This class can give to users of your site or application functionality to calculate a big formula (10000 characters max by default) and get the answer online. The class written in PHP.

Setup and Usage
---------------

Simply include this class into your project like so:

`include_once('/libraries/FormulaParser.php');`

Then invoke the class in your project using the class constructor:

`$formula = new FormulaParser($user_formula, $lang, $max_length, $characters_number);`

`$user_formula` The user's formula given to class

`$lang` Set language ('en', 'ru' or 'es')

`$max_length` Max length of the formula

`$characters_number` The number of characters after the decimal point in calculated answer


The initialized object `$formula` has two public methods to work with the class:

`getResult()` Returns a result array(0=>value1, 1=>value2), where value1 is the operating status, which can be 'done' or 'error', and value2 is a calculated answer or some error message to the preset language in constructor. The successful calculated answer is a float number with several characters after the decimal point.

`getFormula()`  Returns the initially introduced formula

Example
-------

Let's see how easy this class is to use. For example, user's formula is: ((8+(10*(3+5)))/2.1)-5^2

```
$user_formula = '((8+(10*(3+5)))/2.1)-5^2';
$formula = new FormulaParser($user_formula, 'en', 10000, 4);
$result = $formula->getResult(); // returns array (0=>'done', 1=>16.9048)
if ($result[0]=='done') {
  echo "Answer: $result[1]";
} elseif ($result[0]=='error') {
  echo "Error: $result[1]";
}
```

An example of a site made in pure PHP using Formula Parser class: [www.yoursimpleformula.com](http://www.yoursimpleformula.com)

###License

This program is open-sourced software licensed under the dual [MIT license](http://opensource.org/licenses/MIT) & [BSD New](http://www.opensource.org/licenses/BSD-3-Clause).
