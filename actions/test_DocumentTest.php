<?php
class dataface_actions_test_DocumentTest {
	function handle($params){
		$app = Dataface_Application::getInstance();
		$query = $app->getQuery();
		
		header("Content-type: text/json; charset=".$app->_conf['oe']."");
		if ( @$query['row_id'] === '1' ){
			echo json_encode(array(
				'code' => 200,
				'data' => array(
					'row_id' => 1,
					'firstName' => 'Joe',
					'lastName' => 'Montana',
					'age' => 56,
					// Comments only loaded with detail view
					'comments' => 'This is a comment'
				)
			));
		} else if ( @$query['row_id'] === '2'){
			echo json_encode(array(
				'code' => 200,
				'data' => array(
					'row_id' => 2,
					'firstName' => 'Steve',
					'lastName' => 'Hannah',
					'age' => 34,
					'comments' => 'Steve Comments'
				)
			));
		} else if ( @$query['row_id'] === '3' ){
			echo json_encode(array(
				'code' => 200,
				'data' => array(
					'row_id' => 3,
					'firstName' => 'Barry',
					'lastName' => 'White',
					'age' => 99,
					'comments' => 'Barry comments'
				)
			));
		} else if ( @$query['-resultSet'] == '1' and @$query['result_id'] === '2' ){
			echo json_encode(array(
				'code' => 200,
				'rows' => array(
					array(
						'row_id' => 1,
						'firstName' => 'Joe',
						'lastName' => 'Montana',
						'age' => 56
					),
					array(
						'row_id' => 2,
						'firstName' => 'Steve',
						'lastName' => 'Hannah',
						'age' => 34
					),
					array(
						'row_id' => 3,
						'firstName' => 'Barry',
						'lastName' => 'White',
						'age' => 99
					)
				)
			));
		}
		
	}
}
