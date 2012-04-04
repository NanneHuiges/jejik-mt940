<?php

/*
 * This file is part of the Jejik\MT940 library
 *
 * Copyright (c) 2012 Sander Marechal <s.marechal@jejik.com>
 * Licensed under the MIT license
 *
 * For the full copyright and license information, please see the LICENSE
 * file that was distributed with this source code.
 */

namespace Jejik\MT940\Parser;

/**
 * Parser for ABN-AMRO documents
 *
 * @author Sander Marechal <s.marechal@jejik.com>
 */
class AbnAmro extends AbstractParser
{
    /**
     * @var string PCRE sub expression for the delimiter
     */
    protected $statementDelimiter = '-';

    /**
     * Test if the document is an ABN-AMRO document
     *
     * @param string $text
     * @return bool
     */
    public function accept($text)
    {
        return substr($text, 0, 6) === 'ABNANL';
    }

    /**
     * Get the opening balance
     *
     * @param mixed $text
     * @return void
     */
    protected function openingBalance($text)
    {
        if ($line = $this->getLine('60F|60M', $text)) {
            return $this->balance($line);
        }
    }

    /**
     * Get the closing balance
     *
     * @param mixed $text
     * @return void
     */
    protected function closingBalance($text)
    {
        if ($line = $this->getLine('62F|62M', $text)) {
            return $this->balance($line);
        }
    }


    /**
     * Get the contra account from a transaction
     * For SEPA it returns BIC/IBAN numbers
     * @param array $lines The transaction text at offset 0 and the description at offset 1
     * @return string|null
     */
    protected function contraAccount(array $lines)
    {
        if (!isset($lines[1])) {
            return null;
        }
        if(preg_match('/SEPA OVERBOEKING\s*IBAN: ([0-9a-zA-Z]*).*\n?.*BIC: ([0-9a-zA-Z]*).*\n?.*NAAM: ([^\x0D]*)/',$lines[1],$match)){
        	return trim($match[2]).'/'.trim($match[1]);
        }
        if (preg_match('/^([0-9.]{11,14}) /', $lines[1], $match)) {
            return str_replace('.', '', $match[1]);
        }

        if (preg_match('/^GIRO\s*(\d+)\s/', $lines[1], $match)) {
            return $match[1];
        }

        return null;
    }
    
    /**
    * Get the contra account Holder from a transaction
    * For joined accounts suffix "CJ" or suffix/infix "EO" is kept to indicate shared accounts
    * @param array $lines The transaction text at offset 0 and the description at offset 1
    * @return string|null
    */
    protected function contraAccountHolder(array $lines)
    {
    	if (!isset($lines[1])) {
    		return null;
    	}
    	
    	if(preg_match('/SEPA OVERBOEKING\s*IBAN: ([0-9a-zA-Z]*).*\n?.*BIC: ([0-9a-zA-Z]*).*\n?.*NAAM: ([^\x0D]*)/',$lines[1],$match)){
    		return trim($match[3]);    		
    	}else{    	
	    	//name is on either line 1 or line2.
	    	if(substr($lines[1],0,4) == "GIRO"){
	    		$line1 = substr($lines[1],5,27);
	    		$line2 = substr($lines[1],31,31);
	    	}else{
	    		$line1 = substr($lines[1],0,31);
	    		$line2 = substr($lines[1],31,31);
	    	}
	    	
	    	if(preg_match('/^\s*[0-9.]+\s+(\S.*)/',$line1,$match)){//suddenly, a none-whitespace appears!
	    		return trim($match[1]);
	    	}elseif(preg_match('/^\s*[0-9.]+\s+$/',$line1)){//line1 had only accountnr. Name is on line2
	    		if(preg_match('/^\s*([^\x0D]*)/',$line2,$match)){
	    			return trim($match[1]);
	    		}
	    	}
    	}
    	return null;
    }
    
    /**
    * Create a Transaction from MT940 transaction text lines
    * Uses AbstractParser standard lines but adds AbnAmro specific feature for accountholder
    * 
    * @see \Jejik\MT940\Parser\AbstractParser\transaction
    * @param array $lines The transaction text at offset 0 and the description at offset 1
    * @return \Jejik\MT940\Transaction
    */
    protected function transaction(array $lines)
    {
    	$transaction = parent::transaction($lines);
    	$transaction->setContraAccountHolder($this->contraAccountHolder($lines));
    
    	return $transaction;
    }    
    
    
}
