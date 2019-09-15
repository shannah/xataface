<?php

class tables_lesson_plans {

	function created__toString($value){ return $value['month'].'/'.$value['day'].'/'.$value['year'];}
	function modified__toString($value){ return $this->created__toString($value); }

}
