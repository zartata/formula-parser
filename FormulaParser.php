<?php
/**
 * Formula Parser - The PHP class for parsing big mathematical formulas from a string and get results online.
 * It's like what you can do in R, but right on a website.
 *
 * @author Denis Simon <hellodenissimon@gmail.com>
 *
 * @license   This program is a free software and distributed without any warranty under dual MIT & BSD New
 * http://www.opensource.org/licenses/MIT, http://www.opensource.org/licenses/BSD-3-Clause
 *
 * Version: 1.0
 *
 * Date: 2014-09-10
 */
 
/**
* @interface IFormulaParser
*
* Interface includes two public methods to work with the class.
*/
interface IFormulaParser {	
	
	//getResult method returns a result array(0=>value1, 1=>value2), where value1 is the operating status, 
	//which can be 'done' or 'error', and value2 is a calculated answer or error message to the preset language
	//in constructor.
	//The successful calculated answer is a float number with several characters after the decimal point.
	public function getResult();
	
	//getFormula method returns the initially introduced formula.
	public function getFormula();
}

/**
* @class FormulaParser
*/
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
	* @param string $user_formula		  The user's formula given to class
	* @param string $lang				  Set language ('en', 'ru' or 'es')
	* @param integer $max_length	      Max length of the formula
	* @param integer $characters_number	  The number of characters after the decimal point 
	* 									  in calculated answer
	*/
	public function __construct($user_formula, $lang, $max_length, $characters_number)
	{
	    $this->_formula = $this->_original_formula = $user_formula;
		
		$lang_array = array('en','ru','es');
		if (in_array($lang,$lang_array)) {
			$this->_lang = $lang;
		}
		
		$max_length = (int)$max_length;
		if (($max_length<=0)||($max_length>10000)) $max_length = 10000;
		$this->_max_length = $max_length;
		
		$characters_number = (int)$characters_number;
		if (($characters_number<0)||($characters_number>10)) $characters_number = 4;
		$this->_characters_number = $characters_number;
		
		unset($user_formula, $lang, $max_length, $characters_number);
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
	* Returns the initially introduced formula
	*
	* @name getFormula
	* @return string
	*/
	public function getFormula()
	{
	    return $this->_original_formula;	
	}
	
	/**
	* 
	* Sort array by key
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
	* First parse (calculate first-order operations ^, * and /)
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
								$a = round($array[$i-1]/$array[$i+1],10);
							} else {
								//@rule one can not divide by 0
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
	* Second parse (calculate second-order operations + and -)
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
	* Calculate pre result
	*
	* @name getPreResult
	* @param string $str	Part of the formula
	* @return float
	*/
	private function getPreResult($str)
	{
	    //some syntax check		
		if (($str[0]=='+')||($str[0]=='*')||($str[0]=='/')||($str[0]=='^')) {
			$this->_correct = 0;
			return; 
		}
		if ((substr($str, -1)=='+')||(substr($str, -1)=='-')||(substr($str, -1)=='*')
		||(substr($str, -1)=='/')||(substr($str, -1)=='^')) {
			$this->_correct = 0;
			return; 
		}
		
		if (strlen($str)<2) {
			$this->_correct = 0;
			return;
		}
		
		for ($i=0; $i<=strlen($str)-1; $i++) {
			if ($i<strlen($str)-1) {
			if ( (($str[$i]=='+')||($str[$i]=='-')||($str[$i]=='*')||($str[$i]=='/')||($str[$i]=='^')) 
			&& (($str[$i+1]=='+')||($str[$i+1]=='*')||($str[$i+1]=='/')||($str[$i+1]=='^')) ) {
				$this->_correct = 0;
				break;
			} 
			}
		}
		
		for ($i=0; $i<=strlen($str)-1; $i++) {
			if ( (($str[$i]=='+')||($str[$i]=='-')||($str[$i]=='*')||($str[$i]=='/')||($str[$i]=='^')) 
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
		
		//if all is correct now, create and fill $main_array
		$main_array = array();
		$count = 0;
		
		for ($i=0; $i<=strlen($str)-1; $i++) {
			if (($i==0)&&($str[0]=='-')) {
				$main_array[$count] = '-';
			} else {
				if (is_numeric($str[$i])) {
					$main_array[$count] = $main_array[$count].$str[$i];
				} elseif (($str[$i]=='.')&&(is_numeric($str[$i-1]))&&(is_numeric($str[$i+1]))){
					$main_array[$count] = $main_array[$count].$str[$i];	
				} elseif (($str[$i]=='-')&&(($str[$i-1]=='+')||($str[$i-1]=='-')
				||($str[$i-1]=='*')||($str[$i-1]=='/')||($str[$i-1]=='^'))&&(is_numeric($str[$i+1]))){
					$count = $count+1;
					$main_array[$count] = $main_array[$count].$str[$i];	
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
		
		return round($main_array,$this->_characters_number);
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
	* A main method to calculate the user's formula. Returns a result array(0=>value1, 1=>value2), 
	* where value1 is the operating status, which can be 'done' or 'error', and value2 is a calculated answer 
	* or error message to the preset language in constructor.
	*
	* @name getResult
	* @return array
	*/
	public function getResult()
	{
		$result = 0;
		
		$test = ''; $test = $this->_formula;		
		
		////check that the user's formula is correct
		//a couple of first checks
		if ((empty($test))||(!strpbrk($test,'0123456789'))||(!strpbrk($test,'+-*/^'))) {
			if ($this->_lang=='en') {
				$msg = 'You have not entered the formula.';
			} elseif ($this->_lang=='ru') {
				$msg = 'Вы не ввели формулу.';
			} elseif ($this->_lang=='es') {
				$msg = 'Usted no ha entrado en la fórmula.';
			}
		return (array('error',$msg));
		}
		
		if (strlen($test)>$this->_max_length) {
			if ($this->_lang=='en') {
				$msg = 'The formula can contain no more than '.$this->_max_length.' characters.';
			} elseif ($this->_lang=='ru') {
				$msg = 'Формула может включать не более '.$this->_max_length.' символов.';
			} elseif ($this->_lang=='es') {
				$msg = 'La fórmula puede contener no más de '.$this->_max_length.' caracteres.';
			}
		return (array('error',$msg));
		}
		
		//check for an equality of opening and closing parentheses
		$open_count = 0; $close_count = 0;
		for ($i=0; $i<=strlen($test)-1; $i++) {
			if ($test[$i]=='(') {
				$open_count = $open_count+1;
			} elseif ($test[$i]==')') {
				$close_count = $close_count+1;
			}
		}
		if ($open_count!=$close_count) {
			if ($this->_lang=='en') {
				$msg = 'Number of opening and closing parenthesis must be equal.';
			} elseif ($this->_lang=='ru') {
				$msg = 'Количество открывающих и закрывающих скобок должно быть равно.';
			} elseif ($this->_lang=='es') {
				$msg = 'Número de apertura y cierre paréntesis debe ser igual.';
			}   
		return (array('error',$msg));
		}
		//
		
		//check for an absence of extra parentheses as the first and last symbol
		$ok1 = NULL; $ok2 = NULL;
		while ((($test[0]=='(')&&(substr($test, -1)==')'))&&(($ok1!==0)||($ok2!==0))) {
			
			$ok1 = NULL; $ok2 = NULL;
			for ($i=1; $i<=strlen($test)-1; $i++) {
				if ($test[$i]=='(') {
					$ok1 = 1;
				} elseif (($test[$i]==')')&&($ok1!=1)) {
					$ok1 = 0;
				}
				if ($ok1===0) break;
			}
			
			for ($i=strlen($test)-2; $i>=0; $i--) {
				if ($test[$i]==')') {
					$ok2 = 1;
				} elseif (($test[$i]=='(')&&($ok2!=1)) {
					$ok2 = 0;
				}
				if ($ok2===0) break;
			}

			if (($ok1==1)&&($ok2==1)) {
				$test = substr($test, 1); 
				$test = substr($test, 0, strlen($test)-1);
				$this->_formula = $test;
			}
		}
		//
		
		if (strstr($test, '/')) {$test = $this->cutSymbol($test, '/');}
		if (strstr($test, '(')) {$test = $this->cutSymbol($test, '(');}
		if (strstr($test, ')')) {$test = $this->cutSymbol($test, ')');}
		
		if ((preg_match('/[^0-9*+-^.]/',$test))||(strstr($test,' '))){
			if ($this->_lang=='en') {
		    	$msg = 'The formula can contain only numbers, operators +-*/^ and parentheses, no spaces.';
			} elseif ($this->_lang=='ru') {
		    	$msg = 'Формула может содержать только цифры, операторы +-*/^ и скобки, без пробелов.';
			} elseif ($this->_lang=='es') {
		    	$msg = 'La fórmula puede contener cifras, los operadores +-*/^ y paréntesis, sin espacios.';
			}
			return (array('error',$msg));
		////
		} else {
				
			/////////////////////////////////////////////////////////////////////////
			//start iteration algorithm
			$work_formula =''; $processing_formula = ''; $temp = ''; 
			$work_formula = $processing_formula = $this->_formula;
						
			$brackets_count = 0;
			for ($y=0; $y<=strlen($work_formula)-1; $y++) {
				if ($work_formula[$y]=='(') {
					$brackets_count = $brackets_count+1;
				}
			}
			
			//// start big for
			for ($yy=1; $yy<=$brackets_count; $yy++) {
				
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
				
				if ($temp) {
					if (((strstr($temp,'+'))||(strstr($temp,'-'))||(strstr($temp,'*'))||(strstr($temp,'/'))
					||(strstr($temp,'^')))&&((strlen($temp))>=2)) {
						$temp = $this->getPreResult($temp);
			
					} else {
						$this->_correct=0;
						break;
					}
				} else {
					$this->_correct=0;
					break;
				}
				
				if ((substr($processing_formula, -1))!=')') {
					$processing_formula = substr($processing_formula, 0, $start_cursor_pos-1)
					.$temp.substr($processing_formula, (($end_cursor_pos*-1)+1));
				} else {
					$processing_formula = substr($processing_formula, 0, $start_cursor_pos-1)
					.$temp;
				}
			
				if ($this->_correct == 0) {
					break;
				}	
			
			} //// end big for
			
			if ($processing_formula) {
				if (((strstr($processing_formula,'+'))||(strstr($processing_formula,'-'))
				||(strstr($processing_formula,'*'))||(strstr($processing_formula,'/'))
				||(strstr($processing_formula,'^')))&&((strlen($processing_formula))>=3)) {
					$result = $this->getPreResult($processing_formula);
				} else {
					$result = $processing_formula;
				}
			}			
			//end iteration algorithm
			/////////////////////////////////////////////////////////////////////////
		
			if ($this->_correct==1) {	
				return (array('done',$result));
			} else {
				return (array('error',$this->errorMsg()));
			}
		}
	}
}
