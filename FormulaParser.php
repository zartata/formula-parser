<?php
/**
 * Formula Parser - A PHP class for parsing and evaluating mathematical formula entered as a string
 *
 * @author   Denis Simon <hellodenissimon@gmail.com>
 *
 * @license  Licensed under MIT (https://github.com/denissimon/formula-parser/blob/master/LICENSE)
 *
 * @version  2.2.0-2015.01.22
 */
 
interface IFormulaParser {
	
	public function getResult();
	
	public function getFormula();
}
 
class FormulaParser implements IFormulaParser {
	
	/**
	 * The entered text of the formula to handle by getResult method
	 */
	private $_formula = null;
	
	/**
	 * The entered text of the formula to return by getFormula method
	 */
	private $_original_formula = null;
	
	/**
	 * The being evaluated subexpression of the formula
	 */
	private $_expression = null;
	
	/**
	 * Are there any errors during parsing: 1 or 0
	 */
	private $_correct = 1;
	
	/**
	 * A type of error for displaying a right message: 0,1,2 or 3
	 */
	private $_error_type = 0;
	
	/**
	 * The selected language in which messages should be displayed: 'en', 'ru' or 'es'
	 */
	private $_lang = 'en';
	
	/**
	 * The selected precision rounding of the answer
	 */	
	private $_precision_rounding = 4;
		
	/**
	 * Constructor
	 *
	 * @param string	$input_string	        The formula entered as a string
	 * @param string	$language		Setting the language
	 * @param integer	$precision_rounding   Setting the maximum number of digits after the decimal point 
	 * 				        in a calculated answer
	 */
	public function __construct($input_string, $language, $precision_rounding)
	{
		$this->_formula = $this->_original_formula = $input_string;
		
		if (in_array($language, array('en','ru','es'))) {
			$this->_lang = $language;
		}
		
		$this->_precision_rounding = abs((int)$precision_rounding);
		
		unset($input_string, $language, $precision_rounding);
	}
	
	/**
     * Magic overloading method
     *
     * @param string	$name
     * @param array		$arguments
	 *    
	 * @throws Exception when the method doesn't exist
     */
    public function __call($name, $arguments)
    {
		throw new Exception("No such method exists: $name (".implode(', ', $arguments).")");
    }
	
	/**
	 * Returns the text of the formula passed to the constructor
	 *
	 * @return string	The entered formula
	 */
	public function getFormula()
	{
		return $this->_original_formula;	
	}
	
	/**
	 * Helper: sorts a given array by key
	 *
	 * @param array	$array
	 *
	 * @return array
	 */
	private function resortByKey(array $array)
	{
		$new_array = array();
		foreach ($array as $item)
			$new_array[] = $item;
		return $new_array;
	}
	
	/**
	 * Validates the being evaluated subexpression of the formula
	 *
	 * @return boolean
	 */
	private function validation() { 
		if (preg_match('/[^0-9*+-\/^.epi\s\t\(\)]/i', $this->_expression)) {
			return false;
		}
		return true;
	}
				
	/**
	 * Calculates first-order operations ^, * and /
	 *
	 * @param array	$array	An array containing the parsed subexpression of the formula
	 *
	 * @return array
	 */
	private function calculate1(array $array)
	{
		$a = 0;
		if (in_array('^',$array)) {
			for ($i=0; $i<=count($array)-1;$i++) 
			{
				$otp = 1;
				if ($array[$i]==='^') {
					if ((is_numeric($array[$i-1]))&&(is_numeric($array[$i+1]))) {
						if ((!is_numeric($array[$i-1]))||(!is_numeric($array[$i+1]))) {
							$this->_correct=0;
							if (!$this->validation())
								$this->_error_type = 1;
							break;
							return $array;
						}
						if ($array[$i-1]<0) {
							$a = pow($array[$i-1]*-1,$array[$i+1]);
							$otp = 2;
						} else {
							$a = pow($array[$i-1],$array[$i+1]);
						}
					} else {
						$this->_correct=0;
						if (!$this->validation())
							$this->_error_type = 1;
						break;
						return $array;
					}
					unset($array[$i-1],$array[$i+1]);
					if ($otp==1) {
						$array[$i]=$a;
					} else {
						$array[$i]=$a*-1;
					}
					$array = $this->resortByKey($array);
					$i = 0;
				}
			}	
		}
		
		$a = 0;
		if ((in_array('*',$array))||(in_array('/',$array))) {
			for ($i=0; $i<=count($array)-1;$i++) 
			{
				if (($array[$i]==='*')||($array[$i]==='/')) {
					if ((!is_numeric($array[$i-1]))||(!is_numeric($array[$i+1]))) {
						$this->_correct=0;
						if (!$this->validation())
							$this->_error_type = 1;
						break;
						return $array;
					}
					if ($array[$i]==='*') {
						$a = $array[$i-1]*$array[$i+1];
					} elseif ($array[$i]==='/') {
						if ($array[$i+1]!=0) {
							$a = round($array[$i-1]/$array[$i+1],$this->_precision_rounding);
						} else {
							$this->_correct=0;
							if (!$this->validation())
								$this->_error_type = 1;
							break;
							return $array;
						}
					}
					unset($array[$i-1],$array[$i+1]);
					$array[$i]=$a;
					$array = $this->resortByKey($array);
					$i = 0;
				}
			}
		}
		return $array;
	}
	
	/**
	 * Calculates second-order operations + and -
	 *
	 * @param array	$array	An array containing the parsed subexpression of the formula
	 *
	 * @return array
	 */
	private function calculate2(array $array)
	{
		$a = 0;
		if ((in_array('+',$array))||(in_array('-',$array))) {
			for ($i=0; $i<=count($array)-1;$i++) 
			{
				if (($array[$i]==='+')||($array[$i]==='-')) {
					if ((!is_numeric($array[$i-1]))||(!is_numeric($array[$i+1]))) {
						$this->_correct=0;
						if (!$this->validation())
							$this->_error_type = 1;
						break;
						return $array;
					}
					if ($array[$i]==='+') {
						$a = $array[$i-1]+$array[$i+1];
					} elseif ($array[$i]==='-') {
						$a = $array[$i-1]-$array[$i+1];
					}
					unset($array[$i-1],$array[$i+1]);
					$array[$i]=$a;
					$array = $this->resortByKey($array);
					$i = 0;
				}
			}
		}
		return $array;
	}
	
	/**
	 * Parses and evaluates a subexpression of the formula
	 *
	 * @param string $str	A particular portion (subexpression) of the formula.
	 *						It's in parentheses, or the whole formula if there are no parentheses.
	 * @return float
	 */
	private function getAnswer( $str )
	{
		$str = trim($str);
				
		$this->_expression = $str;
		
		$strlen = strlen($str);
		
		$main_array = array();
		
		$count = 0;
		
		for ($i=0; $i<=$strlen-1; $i++) 
		{		
			if (($i==0)&&($str[$i]=='-')) {	
				$main_array[$count] = '-';
			} else {
				// Spaces and tab characters will be skipped
				if (($str[$i]==' ')||($str[$i]=='	')) {
					$count++;		
				// Number
				} elseif (is_numeric($str[$i])) {
					if ($i+1<=$strlen-1) {
						if (!stristr('0123456789.+-*/^e 	', $str[$i+1])) {
							$this->_correct = 0;
							break;
						} else {
							$main_array[$count] = $main_array[$count].$str[$i];
						}
					} else {
						$main_array[$count] = $main_array[$count].$str[$i];
					}
				// Constant pi
				} elseif (strtolower($str[$i-1].$str[$i])=='pi') {
				} elseif (strtolower($str[$i].$str[$i+1])=='pi') {
					if ($i+2<=$strlen-1) {
						if (!strstr('+-*/^ 	',strtolower($str[$i+2]))) {
							$this->_correct = 0;
							break;
						} else {
							$count++; $main_array[$count] = M_PI;
						}
					} else {
						$count = $count+1;
						$main_array[$count] = M_PI;
					}	
				// Number in scientific E notation
				} elseif (strtolower($str[$i])=='e') {
					if ((preg_match('/(e)(?!\d|\+|\-)/i',$str[$i].$str[$i+1]))
					|| (!strstr('0123456789',$str[$i-1]))) { 
						$this->_correct = 0;
						break;
					} else {
						$count++; $main_array[$count] = '*';
						$count++; $main_array[$count] = '10';
						$count++; $main_array[$count] = '^';
						$count++;
					}
				} elseif (($str[$i]=='-')&&(strtolower($str[$i-1])=='e')&&(is_numeric($str[$i+1]))){
					$main_array[$count] = $str[$i];	
				} elseif (($str[$i]=='+')&&(strtolower($str[$i-1])=='e')&&(is_numeric($str[$i+1]))){
				// Decimal point in float
				} elseif (($str[$i]=='.')&&(is_numeric($str[$i-1]))&&(is_numeric($str[$i+1]))){
					$main_array[$count] = $main_array[$count].$str[$i];	
				} else {
					// Operator
					$count++;
					if (stristr('+-*/^', $str[$i])) {
						if (!stristr('0123456789+-p 	', $str[$i+1])) {
							$this->_correct = 0;
							break;
						} else {
							if ($count==1 && $str[$i]=='+' && !$str[$i-1])
								continue;
							$main_array[$count] = $str[$i];
							$count++;
						}
					} else {
						$this->_correct = 0;
						break;	
					}
				}
			}
		}
		
		if (!$this->_correct) {
			if (!$this->validation()) {
				$this->_error_type = 1;
			}
			return 0;
		}
		
		$main_array = $this->resortByKey($main_array);
		
		// Combination of operators
		$temp_array = array();
		$i = 0;
		foreach ($main_array as $item) 
		{
			if (($item==='+')||($item==='-')) {
				if ((($i==0)&&(is_numeric($main_array[$i+1])))
				||  (($i>0)&&(is_numeric($main_array[$i+1]))&&(stristr('+-*/^e',$main_array[$i-1])))) {
					if ($item==='+') {
						$temp_array[] = $main_array[$i+1];
					} else {
						if (($main_array[$i-1]==='-')&&($main_array[$i-2]!=='-')) {
								$temp_array[] = $main_array[$i+1];
						} elseif (($main_array[$i-1]==='-')&&($main_array[$i-2]==='-')) {
							$this->_correct = 0;
							break;
						} else { 
							$temp_array[] = $item.$main_array[$i+1];
						}
					}
				} else {
					if (($item==='-')&&($main_array[$i+1]==='-')) {
						if ($temp_array)
							$temp_array[] = '+';
					} elseif (($item==='-')&&($main_array[$i+1]==='+')) {
							if ($temp_array) {
								$temp_array[] = '+';
							}
							$temp_array[] = '0';
							$temp_array[] = '-';
					} else {
						$temp_array[] = $item;
					}
				}
			} elseif ((($i==1)&&(is_numeric($item))&&(strstr('+-',$main_array[$i-1])))
			|| (($i>1)&&(is_numeric($item))&&(strstr('+-',$main_array[$i-1]))
			&&(stristr('+-*/^e',$main_array[$i-2])))) {
			} else {
				$temp_array[] = $item;
			}
			$i++;
		}
		
		$main_array = $temp_array;
		
		// Get the answer
		$main_array = $this->calculate1($main_array);
		$main_array = $this->calculate2($main_array);
		
		if (count($main_array)!=1) {
			$this->_correct=0;
		}
		
		return round($main_array[0], $this->_precision_rounding);
	}
	
	/**
	 * Checks if there is an exponential expression like '(-2) ^ 4',
	 * where the base is a negative number in parentheses.
	 * If yes - calculates it correctly.
	 *
	 * @param string	$expression
	 * @param integer	$length
	 * @param integer	$cursor
	 * @param float		$base
	 *
	 * @return mixed
	 */
	private function checkExp($expression, $length, $cursor, $base) {
		$response = null;
		if ($base<0) {
			$expression = substr($expression, $length-$cursor+1);
			$test_exp = ltrim($expression);
			if ($test_exp[0]=='^') {
				$exp = '';
				for ($q=0; $q<=$cursor-1; $q++) {
					if ($expression[$q]=='^') {
						$exp = ' ';
					} elseif (($exp!='')&&(!strstr('^ 	',$expression[$q]))) {
						if ((strstr('+-',$expression[$q]))&&($exp==' ')) {
							$exp .= $expression[$q];
						} elseif (strstr('0123456789.(',$expression[$q])) {
							if ($expression[$q]!='(')
								$exp .= $expression[$q];
						} else {
							$exp = trim($exp);
							$cursor = $cursor - $q;
							if ($exp[0]=='+')
								$exp = substr($exp, 1);
							break;
						}
					} elseif (strstr(' 	',$expression[$q])) {
					}
				}
			$response = new stdClass;
			$response->cursor = $cursor;
			$response->result = pow(abs($base),$exp) * pow(-1,$exp);
			}
		}
		return $response;
	}
		
	/**
	 * Returns an error message in the set language
	 *
	 * @return string
	 */
	private function errorMsg()
	{
		// Syntax error
		if ($this->_error_type==1) {
			if ($this->_lang=='en') {
			return 'The formula can contain only numbers, operators +-*/^, supported constants 
			and parentheses.';
			} elseif ($this->_lang=='ru') {
				return 'Формула может содержать только цифры, операторы +-*/^, поддерживаемые константы 
				и скобки.';
			} elseif ($this->_lang=='es') {
				return 'La fórmula puede contener cifras, los operadores +-*/^, soportadas constantes 
				y paréntesis.';
			}
		// Input string error
		} elseif ($this->_error_type==2) {
			if ($this->_lang=='en') {
			return 'You have not entered the formula.';
			} elseif ($this->_lang=='ru') {
				return 'Вы не ввели формулу.';
			} elseif ($this->_lang=='es') {
				return 'Usted no ha entrado en la fórmula.';
			}
		// Mismatched parentheses error
		} elseif ($this->_error_type==3) {
			if ($this->_lang=='en') {
				return 'Number of opening and closing parenthesis must be equal.';
			} elseif ($this->_lang=='ru') {
				return 'Количество открывающих и закрывающих скобок должно быть равно.';
			} elseif ($this->_lang=='es') {
				return 'Número de apertura y cierre paréntesis debe ser igual.';
			}
		// Unexpected error
		} else {
			if ($this->_lang=='en') {
				return 'Please check the specified formula for syntactic correctness.';
			} elseif ($this->_lang=='ru') {
				return 'Пожалуйста, проверьте указанную формулу на корректность синтаксиса.';
			} elseif ($this->_lang=='es') {
				return 'Por favor, compruebe la fórmula especificada para la corrección sintáctica.';
			}
		}
	}
			
	/** 
	 * Parses and evaluates the entered formula
	 *
	 * @return array	array(0=>value1, 1=>value2), where value1 is the operating status, 
	 * 			which can be 'done' or 'error', and value2 is a calculated answer 
	 * 			or error message in the set language. 
	 */
	public function getResult()
	{
		$this->_formula = trim($this->_formula);
		
		// Check that the formula has been entered
		if ($this->_formula[0]=='') {
			$this->_correct = 0;
			$this->_error_type = 2;
			//goto finish;
		}

		if ($this->_correct) {
					
			$open_parentheses_count = substr_count($this->_formula,'('); 
			$close_parentheses_count = substr_count($this->_formula,')');
			
			if ($open_parentheses_count > 0 || $close_parentheses_count > 0) {
				
				// Check for an equality of opening and closing parentheses
				if ($open_parentheses_count != $close_parentheses_count) {
					$this->_correct = 0;
					$this->_error_type = 3;
					//goto finish;
				}
									
				// Check the syntax is correct when using parentheses
				if (preg_match('/(\)[\s\t]*[^\)\+\-\*\/\^\s\t])|(\([\s\t]*?\))|([^\(\+\-\*\/\^\s\t][\s\t]*\()/',$this->_formula)) {
					$this->_correct = 0;
					//goto finish;
				}
				
				if ($this->_correct) {
						
					$temp = '';
					$processing_formula = $this->_formula;
					
					// Select of subexpressions in parentheses
					while ( strstr($processing_formula,'(') || strstr($processing_formula,')') ) 
					{
						$start_cursor_pos = 0; $end_cursor_pos = 0;
						$temp = $processing_formula;
						
						while (strstr($temp,'(')) 
						{
							$strlen_temp = strlen($temp);
							for ($i=0; $i<=$strlen_temp-1; $i++) {
								if ($temp[$i]=='(') {
									$temp = substr($temp, $i+1);
									$start_cursor_pos = $start_cursor_pos+$i+1;
								}
							}
						}
						
						$strlen_temp = strlen($temp);
						for ($i=0; $i<=$strlen_temp-1; $i++) 
						{
							if ($temp[$i]==')') {
								$end_cursor_pos = $strlen_temp-$i;
								$temp = substr($temp, 0, $i);
								break;
							}
						}
						
						$length = strlen($processing_formula);
						
						if (!empty($temp)) {
							$temp = $this->getAnswer($temp);
							$checkExp = $this->checkExp($processing_formula,$length,$end_cursor_pos,$temp);
							if ($checkExp->result) {
								$temp = $checkExp->result;
								$end_cursor_pos = $checkExp->cursor;
							}
						}
						
						// Optimize excess parentheses to dynamically reduce the number of iterations
						if (($processing_formula[$start_cursor_pos-2]=='(') 
						&& ($processing_formula[$length-$end_cursor_pos+2]==')')) {
							$processing_formula = substr($processing_formula, 0, $start_cursor_pos-2)
							.$temp.substr($processing_formula, $length-$end_cursor_pos+2);	
						} else {
							$processing_formula = substr($processing_formula, 0, $start_cursor_pos-1)
							.$temp.substr($processing_formula, $length-$end_cursor_pos+1);
						}
					}
					
					$this->_formula = $processing_formula;
				}
			}
			$result = $this->getAnswer($this->_formula);
		}
		
		//finish:
		
		if ($this->_correct) {	
			return (array('done', $result));
		} else {
			return (array('error', $this->errorMsg()));
		}
	}
}
