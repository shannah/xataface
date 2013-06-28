<?php
interface xatacard_layout_DataSource {
	
	
	public function loadRecord( xatacard_layout_Schema $schema, array $query );
	public function newRecord( xatacard_layout_Schema $schema, array $values);
	public function loadRecords( xatacard_layout_Schema $schema, $query);
	
	public function save(xatacard_layout_Record $record);
	public function delete(xatacard_layout_Record $record);
	
	
}
