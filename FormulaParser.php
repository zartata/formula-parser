<?php
/**
 * Formula Parser - A PHP class for parsing of mathematical formula entered as a string
 *
 * @author   Denis Simon <hellodenissimon@gmail.com>
 *
 * @license  Licensed under MIT (https://github.com/denissimon/formula-parser/blob/master/LICENSE)
 *
 * @version  2.0.0-2015.01.16
 */
 
interface IFormulaParser {
	
	public function getResult();
	
	public function getFormula();
}
 
class FormulaParser implements IFormulaParser {
	
	private $_formula = NULL;
	
	private $_original_formula = NULL;
	
	private $_correct = 1;
	
	private $_lang = 'en';
	
	private $_max_length = 10000;
	
	private $_characters_number = 4;
	
	/**
	 *
	 * Constructor
	 *
	 * @param string  $input_string	        The formula entered as a string
	 * @param string  $lang		        Setting the language ('en', 'ru' or 'es')
	 * @param integer $max_length	        Setting the maximum possible length of the formula
	 * @param integer $characters_number    Setting the maximum number of characters after the decimal point 
	 * 				        in a calculated answer
	 */
	public function __construct($input_string, $lang, $max_length, $characters_number)
	{
		$this->_formula = $this->_original_formula = $input_string;
		
		$lang_array = array('en','ru','es');
		if (in_array($lang, $lang_array)) {
			$this->_lang = $lang;
		}
		
		if ($max_length<=0) $max_length = 10000;
		$this->_max_length = (int)$max_length;
		
		if ($characters_number<0) $characters_number = 4;
		$this->_characters_number = (int)$characters_number;
		
		unset($input_string, $lang, $max_length, $characters_number);
	}
	
	/**
	 * @name getFormula
	 * @return string	The initially entered formula
	 */
	public function getFormula()
	{
		return $this->_original_formula;	
	}
	
	/**
	 * @name cutSymbol
	 * @return string
	 */
	private function cutSymbol($str, $symbol)
	{
		return str_replace($symbol, '', $str);	
	}
	
	/**
	 * 
	 * Sort an array by key
	 *
	 * @name reKeyArray
	 * @return array
	 */
	private function reKeyArray(array $array)
	{
		$new_array = array();
		foreach ($array as $item)
			$new_array[] = $item;
		return $new_array;
	}
	
	/**
	 *
	 * Calculate first-order operations ^, * and /
	 *
	 * @name calculate1
	 * @return array
	 */
	private function calculate1(array $array)
	{
		$a = 0;
		if (in_array('^',$array)) {
			for ($i=0; $i<=count($array)-1;$i++) {
				$otp = 1;
				if ($array[$i]==='^') {
					if ((is_numeric($array[$i-1]))&&(is_numeric($array[$i+1]))) {
						if ($array[$i-1]<0) {
							$a = pow($array[$i-1]*-1,$array[$i+1]);
							$otp = 2;
						} else {
							$a = pow($array[$i-1],$array[$i+1]);
						}
					} else {
						$this->_correct=0;
						break;
						return;
					}
					unset($array[$i-1],$array[$i+1]);
					if ($otp==1) {
						$array[$i]=$a;
					} else {
						$array[$i]=$a*-1;
					}
					$array = $this->reKeyArray($array);
					$i = 0;
				}
			}	
		}
		
		$a = 0;
		if ((in_array('*',$array))||(in_array('/',$array))) {
			for ($i=0; $i<=count($array)-1;$i++) {
				if (($array[$i]==='*')||($array[$i]==='/')) {
					if ($array[$i]==='*') {
						$a = $array[$i-1]*$array[$i+1];
					} elseif ($array[$i]==='/') {
						if ($array[$i+1]!=0) {
							$a = round($array[$i-1]/$array[$i+1],$this->_characters_number);
						} else {
							// @rule  one can not divide by 0
							$this->_correct=0;
							break;
							return;
						}
					}
					unset($array[$i-1],$array[$i+1]);
					$array[$i]=$a;
					$array = $this->reKeyArray($array);
					$i = 0;
				}
			}
		}
		return $array;
	}
	
	/**
	 *
	 * Calculate second-order operations + and -
	 *
	 * @name calculate2
	 * @return array
	 */
	private function calculate2(array $array)
	{
		$a = 0;
		if ((in_array('+',$array))||(in_array('-',$array))) {
			for ($i=0; $i<=count($array)-1;$i++) {
				if (($array[$i]==='+')||($array[$i]==='-')) {
					if ($array[$i]==='+') {
						$a = $array[$i-1]+$array[$i+1];
					} elseif ($array[$i]==='-') {
						$a = $array[$i-1]-$array[$i+1];
					}
					unset($array[$i-1],$array[$i+1]);
					$array[$i]=$a;
					$array = $this->reKeyArray($array);
					$i = 0;
				}
			}
		}	
		if (count($array)!=1) {
			$this->_correct=0;
			return;
		} else {
			return $array[0];
		}
	}
	
	/**
	 *
	 * Calculate a subexpression of the formula
	 *
	 * @name getSubexpressionResult
	 * @param string $str	A particular portion (subexpression) of the formula
	 * @return float
	 */
	private function getSubexpressionResult( $str )
	{
		// transform numbers in scientific E notation
		if (stristr($str,'e')) {
			$str = strtolower($str);
			if (preg_match('/(e)(?!\0|+|-)/',$str)) {
				return (array('error',$this->errorMsg()));
			}
			$str = str_replace("e0", "*10^0", $str);
			$str = str_replace("e+", "*10^", $str);
			$str = str_replace("e-", "*10^-", $str);
		}
		
		// begin syntax checks
		if (strlen($str)<2) {
			$this->_correct = 0;
			return;
		}
		
		$str = $this->syntaxExtraCheck($str);
		
		for ($i=0; $i<=strlen($str)-1; $i++) {
			if ($i<strlen($str)-1) {
				if ((($str[$i]=='+')||($str[$i]=='-')||($str[$i]=='*')||($str[$i]=='/')
				||($str[$i]=='^'))
				&& (($str[$i+1]=='*')||($str[$i+1]=='/')||($str[$i+1]=='^'))) {
					$this->_correct = 0;
					break;
				} 
			}
		}
		
		for ($i=0; $i<=strlen($str)-1; $i++) {
			if ((($str[$i]=='+')||($str[$i]=='-')||($str[$i]=='*')||($str[$i]=='/')||($str[$i]=='^')) 
			&& (($str[$i+1]=='+')||($str[$i+1]=='-')||($str[$i+1]=='*')||($str[$i+1]=='/')
			||($str[$i+1]=='^')) 
			&& (($str[$i+2]=='+')||($str[$i+2]=='-')||($str[$i+2]=='*')||($str[$i+2]=='/')
			||($str[$i+2]=='^'))) {
				$this->_correct = 0;
				break;
			}
		}
		//
		
		if ($this->_correct==0) {return;}
		
		// If everything is correct now, create and fill $main_array
		$main_array = array();
		$count = 0;
		
		for ($i=0; $i<=strlen($str)-1; $i++) {
			if (($i==0)&&($str[0]=='-')) {
				$main_array[$count] = '-';
			} else {
				if (is_numeric($str[$i])) {
					$main_array[$count] = $main_array[$count].$str[$i];
				} elseif (($str[$i]=='.')&&(!is_numeric($str[$i-1]))&&(is_numeric($str[$i+1]))){
					$count = $count+1;
					$main_array[$count] = '0'.$str[$i];
				} elseif (($str[$i]=='.')&&(is_numeric($str[$i-1]))&&(is_numeric($str[$i+1]))){
					$main_array[$count] = $main_array[$count].$str[$i];	
				} elseif (($str[$i]=='-')&&(!is_numeric($str[$i-1]))&&(is_numeric($str[$i+1]))){
					$count = $count+1;
					$main_array[$count] = $main_array[$count].$str[$i];
				} elseif (($str[$i]=='+')&&(!is_numeric($str[$i-1]))&&(is_numeric($str[$i+1]))){				
				} else {
					$count = $count+1;
					if (($str[$i]=='+')||($str[$i]=='-')||($str[$i]=='*')||($str[$i]=='/')
					||($str[$i]=='^')) {
						$main_array[$count] = $str[$i];
						$count = $count+1;
					}
				}
			}
		}
		//
				
		$main_array = $this->reKeyArray($main_array);
		
		$main_array = $this->calculate1($main_array);
		$main_array = $this->calculate2($main_array);
				
		return round($main_array, $this->_characters_number);
	}
	
	/**
	 *
	 * Extra syntax check of the formula
	 *	
	 * @name syntaxExtraCheck
	 * @param  $str				A particular portion (subexpression) of the formula
	 * @return string
	 */
	private function syntaxExtraCheck ( $str ) {
		if (($str[0]=='*')||($str[0]=='/')||($str[0]=='^')||($str[0]=='.')) {
			$this->_correct = 0;
		}
		if ($str[0]=='+') {$str = substr($str, 1);}
		
		$substr = substr($str, -1);
		if (($substr=='+')||($substr=='-')||($substr=='*')||($substr=='/')||($substr=='^')
		||($substr=='.')) {
			$this->_correct = 0;
		}
		return $str;
	}
	
	/**
	 * @name errorMsg
	 * @return string
	 */
	private function errorMsg()
	{
		if ($this->_lang=='en') {
			return 'Please check the specified formula for syntactic correctness.';
		} elseif ($this->_lang=='ru') {
			return 'Пожалуйста, проверьте указанную формулу на корректность синтаксиса.';
		} elseif ($this->_lang=='es') {
			return 'Por favor, compruebe la fórmula especificada por la corrección sintáctica.';
		}
	}
	
	/**
	 * 
	 * A main method of the class
	 *
	 * @name getResult
	 * @return array	array(0=>value1, 1=>value2), where value1 is the operating status, which can be 
	 *			'done' or 'error', and value2 is a calculated answer or error message in the set language. 
	 *			The successful calculated answer is a float.
	 */
	public function getResult()
	{
		$this->_formula = trim($this->_formula);
				
		// transform constants Pi
		if (stristr($this->_formula,'pi')) {
			$this->_formula = strtolower($this->_formula);
			if ((preg_match('/(\d|e)(?=pi)/',$this->_formula))||(preg_match('/(pi)(?=\d|e)/',$this->_formula))) {
				return (array('error',$this->errorMsg()));
			}
			$this->_formula = str_replace("pi", M_PI, $this->_formula);
		}
		
		//// begin the validation of the formula
		if (($this->_formula=='')||(!strpbrk($this->_formula,'0123456789'))) {
			if ($this->_lang=='en') {
				$msg = 'You have not entered the formula.';
			} elseif ($this->_lang=='ru') {
				$msg = 'Вы не ввели формулу.';
			} elseif ($this->_lang=='es') {
				$msg = 'Usted no ha entrado en la fórmula.';
			}
			return (array('error',$msg));
		}
		
		if (strlen($this->_formula)>$this->_max_length) {
			if ($this->_lang=='en') {
				$msg = 'The formula can contain no more than '.$this->_max_length.' characters.';
			} elseif ($this->_lang=='ru') {
				$msg = 'Формула может включать не более '.$this->_max_length.' символов.';
			} elseif ($this->_lang=='es') {
				$msg = 'La fórmula puede contener no más de '.$this->_max_length.' caracteres.';
			}
			return (array('error',$msg));
		}
		
		// check for an equality of opening and closing parentheses
		$open_count = substr_count($this->_formula,'('); $close_count = substr_count($this->_formula,')');
		if (($open_count>0||$close_count>0)&&($open_count!=$close_count)) {
			if ($this->_lang=='en') {
				$msg = 'Number of opening and closing parenthesis must be equal.';
			} elseif ($this->_lang=='ru') {
				$msg = 'Количество открывающих и закрывающих скобок должно быть равно.';
			} elseif ($this->_lang=='es') {
				$msg = 'Número de apertura y cierre paréntesis debe ser igual.';
			}   
			return (array('error',$msg));
		}
			
		// check for syntactic correctness
		if ((strstr($this->_formula, ')('))||(strstr($this->_formula, '()'))) {
			return (array('error',$this->errorMsg()));
		}
		if ((preg_match('/(\d)(?=\()/',$this->_formula))||(preg_match('/(\))(?=\d)/',$this->_formula))) {
			return (array('error',$this->errorMsg()));
		}
		$this->_formula = $this->syntaxExtraCheck($this->_formula);
		
		$result = 0;
		$test = $this->_formula;
		
		if (strstr($test, '/')) {$test = $this->cutSymbol($test, '/');}
		if (strstr($test, '(')) {$test = $this->cutSymbol($test, '(');}
		if (strstr($test, ')')) {$test = $this->cutSymbol($test, ')');}
		
		if ((preg_match('/[^0-9*+-^.e]/',$test))||(strstr($test,' '))){
			if ($this->_lang=='en') {
				$msg = 'The formula can contain only numbers, operators +-*/^ and parentheses, no spaces.';
			} elseif ($this->_lang=='ru') {
				$msg = 'Формула может содержать только цифры, операторы +-*/^ и скобки, без пробелов.';
			} elseif ($this->_lang=='es') {
				$msg = 'La fórmula puede contener cifras, los operadores +-*/^ y paréntesis, sin espacios.';
			}
			return (array('error',$msg));
		}
		$test = NULL;
		////
		
		if ($this->_correct==0) { return (array('error',$this->errorMsg())); }
		
		$temp = '';
		$processing_formula = $this->_formula;
			
		//// run an iterative algorithm
		while (strstr($processing_formula,'(')||strstr($processing_formula,')')) {	
			$start_cursor_pos = 0; $end_cursor_pos = 0;
			$temp = $processing_formula;
			
			while (strstr($temp,'(')) {
				for ($i=0; $i<=strlen($temp)-1; $i++) {
					if ($temp[$i]=='(') {
						$temp = substr($temp, $i+1);
						$start_cursor_pos = $start_cursor_pos+$i+1;
					}
				}
			}
			
			for ($ii=0; $ii<=strlen($temp)-1; $ii++) {
				if ($temp[$ii]==')') {
					$end_cursor_pos = ((strlen($temp))-$ii);
					$temp = substr($temp, 0, $ii);
					break;
				}
			}
			
			if (($temp)=='0') $temp = '0+0';	
			if (($temp)&&(((((strstr($temp,'+'))||(strstr($temp,'-'))||(strstr($temp,'*'))
			||(strstr($temp,'/'))||(strstr($temp,'^')))&&((strlen($temp))>=2)))
			||(is_numeric($temp)))){
				$temp = $this->getSubexpressionResult($temp.'+0');
			} else {	
				$this->_correct=0;
				break;
			}
			
			// optimize excess parentheses to reduce the number of iterations,
			// and rewrite at once $processing formula for the next iteration
			if (($processing_formula[$start_cursor_pos-2]=='(') 
			&& ($processing_formula[strlen($processing_formula)-$end_cursor_pos+1]==')')) {
				$processing_formula = substr($processing_formula, 0, $start_cursor_pos-2)
				.$temp.substr($processing_formula, strlen($processing_formula)-$end_cursor_pos+2);	
			} else {
				$processing_formula = substr($processing_formula, 0, $start_cursor_pos-1)
				.$temp.substr($processing_formula, strlen($processing_formula)-$end_cursor_pos+1);
			}
			
			if ($this->_correct == 0) {
				break;
			}	
		}
		
		if ($processing_formula) {
			if (((strstr($processing_formula,'+'))||(strstr($processing_formula,'-'))
			||(strstr($processing_formula,'*'))||(strstr($processing_formula,'/'))
			||(strstr($processing_formula,'^')))&&(strlen($processing_formula)>=2)) {
				$result = $this->getSubexpressionResult($processing_formula);
			} else {
				$result = round($processing_formula,$this->_characters_number);
			}
		}
		////
		
		if ($this->_correct==1) {	
			return (array('done',$result));
		} else {
			return (array('error',$this->errorMsg()));
		}
	}
}
