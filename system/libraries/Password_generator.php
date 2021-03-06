<?php 
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.2.4 or newer
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (http://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2015, British Columbia Institute of Technology (http://bcit.ca/)
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	http://codeigniter.com
 * @since	Version 3.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CodeIgniter Password Generator Class
 *
 * Password generator
 *
 * @package		CodeIgniter
 * @subpackage		Libraries
 * @category		Libraries
 * @author		Scott Arciszewski
 * @link		N/A
 */
 
class CI_Password_generator
{
	public $rules = array(
		'digits' => TRUE,
		'lower' => TRUE,
		'upper' => TRUE,
		'special' => TRUE
	);
 	
 	public function __construct()
 	{
 		$rules = config_item('password_rules');
 		if (!empty($rules)) {
 			$this->rules = $rules;
 		}
 	}
 	
	// --------------------------------------------------------------------
	
	/**
	 * Simple API
	 * 
	 * @param	int	$length	How many characters to generate
	 * @param	array	$rules	Explicitly enable/disable certain character 
	 * 				types ('digit', 'upper', 'lower', 'special')
	 */
	public function create_password(
		$length = 16,
		array $rules = NULL
	) {
		$rules = isset($rules) ? $rules : $this->rules;
		$keyspace = '';
		
		// We build the keyspace here, based on the rules provided
		if (!isset($rules['lower'])) {
			$rules['lower'] = FALSE;
		}
		if (!isset($rules['upper'])) {
			$rules['upper'] = FALSE;
		}
		if (!isset($rules['digits'])) {
			$rules['digits'] = FALSE;
		}
		if (!isset($rules['special'])) {
			$rules['special'] = FALSE;
		}
		
		// Include lowercase characters?
		$rules['lower'] && $keyspace .= 'abcdefghijklmnopqrstuvwxyz';
		
		// Include uppercase characters?
		$rules['upper'] && $keyspace .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		
		// Include numerical characters?
		$rules['digits'] && $keyspace .= '0123456789';
		
		// Include other ASCII printable special characters?
		$rules['special'] && $keyspace .= '`~!@#$%^&*()+=_-[]{}:;\'"\\|,./<>?';
		
		if ($rules === '') {
			// With no keyspace to work with, we cannot return anything.
			return FALSE;
		}
		return $this->get_random_string($length, $keyspace);
	}
	 
	// --------------------------------------------------------------------
	
	/**
	 * Return a random string with an arbitrary character set. Suitable for
	 * generating random passwords.
	 * 
	 * @param	int	$length	The length of the desired string
	 * @return	string	$charset	The characters allowed in the output
	 * 					(defaults to all 94 printable ASCII chars)
	 */
 	public function get_random_string(
		$length = 16,
		$keyspace = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789`~!@#$%^&*()+=_-[]{}:;\'"\\|,./<>?'
	) {
		$pw = '';
		for ($i = 0; $i < $length; ++$i) {
			// Get a random number between 0 and the 1 less than length of $keyspsace,
			// This will give you an index between 0 and the highest character
			// index in $keyspace.
			//
			// For example, if keyspace is '0123456789' then $j will be an
			// integer between 0 and 9.
			
			$j = $this->get_random_number(0, strlen($keyspace) - 1);
            if ($j === FALSE) {
                return FALSE;
            }
			
			// Now let's add the character at $j in $keyspace to our
			// generated password ($pw)
			$pw .= $keyspace[$j];
		}
		return $pw;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Return a random integer between $min and $max, generated by a 
	 * Cryptographically Secure Pseudo-Random Number Generator
	 * 
	 * @param	int	$min	Minimum Value
	 * @param	int	$max	Maximum value
	 * @return	int
	 */
	public function get_random_number($min, $max) 
	{
		$range = $max - $min;
		if ($range < 1) {
			// There was no room for variation!
			return $min;
		}
        ++$range;
		
		// 7776 -> 13
		$bits = ceil(log($range)/log(2));
		
		// 2^13 - 1 == 8191 or 0x00001111 11111111
		$mask = ceil(pow(2, $bits)) - 1;
		
		do {
			// Grab a random integer
			$val = $this->_random_positive_int();
			if ($val === FALSE) {
				// RNG failure
				return FALSE;
			}

            // Apply mask
            $val = $val & $mask;
			
			// If $val is larger than the maximum acceptable number for
			// $min and $max, we discard and try again.
			
		} while ($val >= $range);
		return (int) ($min + $val);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Return a integer in the range [0, PHP_INT_MAX) via a
	 * cryptographically secure random number generator
	 * 
	 * @return	int
	 */
	protected function _random_positive_int() {
		$CI =& get_instance();
		
		// Get some random bytes
		$buf = $CI->security->get_random_bytes(PHP_INT_SIZE);
		if($buf === FALSE) {
			show_error('An unknown random number failure has occurred.');
			return FALSE;
		}
		// Initialize some variables
		$val = 0;
		$i = strlen($buf);
		
		// Take each byte:
		// 1. Bit-shift the accumulated value 8 places to the left.
		//    (1 => 256, 128 => 32768, etc)
		// 2. Convert the current byte to an integer
		// 3. Add the current byte (using bitwise OR) to the accumulated
		//    value
		
		do {
			$i--;
			$val <<= 8;
			$val |= ord($buf[$i]);
		} while ($i != 0);
		
		// Return the random value, masked with the largest integer supported by PHP
		return $val & PHP_INT_MAX;
	}
}

/* End of file Password_generator.php */
/* Location: ./system/libraries/Password_generator.php */
