<?php
/**
 * An ontology to represent a person.  Generally people have:
 * Name or First name and last name
 * Email
 * Phone
 * Address
 * City
 * State
 * Postal Code
 * Country
 */

class Dataface_Ontology_Person extends Dataface_Ontology {
	function buildAttributes(){
		$this->fieldnames = array();
		$this->attributes = array();
		
		$email = null;
		// First let's find the email field.
		
		// First we'll check to see if any fields have been explicitly 
		// flagged as email address fields.
		foreach ( $this->table->fields(false,true) as $field ){
			if ( @$field['email'] ){
				$email = $field['name'];
				break;
			}
		}
		if ( !isset($email) ){
			// Next lets see if any of the fields actually contain the word
			// email in the name
			$candidates = preg_grep('/(email)/i', array_keys($this->table->fields()));
			foreach ( $candidates as $candidate ){
				if ( $this->table->isChar($candidate) ){
					$email = $candidate;
					break;
				}
			}
		}
		
		if ( isset($email) ){
			$field =& $this->table->getField($email);
			$this->attributes['email'] =& $field;
			unset($field);
			$this->fieldnames['email'] = $email;
		}
		
		return true;
		
	}
	
	function validate_email($value, $allowBlanks=true){
		if ( !$allowBlanks and !trim($value) ) return false;
		
		return preg_match('/^[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $value);
	}
	
}
