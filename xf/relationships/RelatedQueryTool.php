<?php
namespace xf\relationships;

class RelatedQueryTool {
    private $query;
    private $record;
    private $includeLimits = true;
    private $includeOrderBy = true;
    
    

    
    public function __construct() {
        $app = \Dataface_Application::getInstance();
        $this->record = $app->getRecord();
        $this->query = $app->getQuery();
        
    }
    
    
    public function setIncludeLimits($includeLimits) {
        $this->includeLimits = $includeLimits;
    }
    
    public function setIncludeOrderBy($includeOrderBy) {
        $this->includeOrderBy = $includeOrderBy;
    }
    
    
    
    public function getSQL($options=[], $query = []) {
        $query = array_merge($this->query, $query);
        foreach ($query as $k=>$v) {
            if ($v === null) {
                unset($query[$k]);
            }
        }
        $table = $this->record->table();
        $relationship = $table->getRelationship($query['-relationship']);
        $sort = $this->includeOrderBy ? $query : 0;
        $sql = $relationship->getSQL($options, $query, $sort);
		$sql = $this->record->parseString($sql);
        if ($this->includeLimits) {
            $start = isset($query['-related:start']) ? $query['-related:start'] : 0;
            $limit = isset($query['-related:limit']) ? $query['-related:limit'] : 30;
            $sql .= " LIMIT ".addslashes($start).",".addslashes($limit);
        }
        
        return $sql;
    }
}
?>