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
     * Get the contra account from a transaction
     *
     * @param array $lines The transaction text at offset 0 and the description at offset 1
     * @return string|null
     */
    protected function contraAccount(array $lines)
    {
        if (!isset($lines[1])) {
            return null;
        }

        if (preg_match('/^([0-9.]{11,14}) /', $lines[1], $match)) {
            return str_replace('.', '', $match[1]);
        }

        if (preg_match('/^GIRO([0-9 ]{9}) /', $lines[1], $match)) {
            return $match[1];
        }

        return null;
    }
    
    /**
    * Get the contra account from a transaction
    *
    * @param array $lines The transaction text at offset 0 and the description at offset 1
    * @return string|null
    */
    protected function contraAccountHolder(array $lines)
    {
    	if (!isset($lines[1])) {
    		return null;
    	}
    	
    	if (preg_match('/^[0-9.]{11,14}\s+(.+?)\s\s+/', $lines[1], $match)) {
        	return trim($match[1]);
        }
        if (preg_match('/^GIRO[0-9 ]{9}\s+(.+?)\s\s+/', $lines[1], $match)) {
        	return trim($match[1]);
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
