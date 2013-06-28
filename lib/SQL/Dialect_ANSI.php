<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2004 Brent Cook                                        |
// +----------------------------------------------------------------------+
// | This library is free software; you can redistribute it and/or        |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This library is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// |                                                                      |
// | You should have received a copy of the GNU Lesser General Public     |
// | License along with this library; if not, write to the Free Software  |
// | Foundation, Inc., 59 Temple Place, Suite 330,Boston,MA 02111-1307 USA|
// +----------------------------------------------------------------------+
// | Authors: Brent Cook <busterbcook@yahoo.com>                          |
// +----------------------------------------------------------------------+
//
// $Id: Dialect_ANSI.php,v 1.2 2006/01/12 22:45:25 sjhannah Exp $
//

// define tokens accepted by the SQL dialect.
$dialect = array(

'commands'=>array('alter','create','drop','select','delete','insert','update'),
'constants' => array('current_date','current_timestamp','current_time'),
'expression_operators'=>array('+','-','*','/'),

'operators'=>array('=','<>','<','<=','>','>=','like','clike','slike','not','is','in','between','and','or'),

'types'=>array('character','char','varchar','nchar','bit','numeric','decimal','dec','integer','int','smallint','float','real','double','date','time','timestamp','interval','bool','boolean','set','enum','text'),

'conjunctions'=>array('by','as','on','into','from','where','with'),

'functions'=>array('avg','count','max','min','sum','nextval','currval','now','length'),

'reserved'=>array('absolute','action','add','all','allocate','and','any','are','asc','ascending','assertion','at','authorization','begin','bit_length','both','cascade','cascaded','case','cast','catalog','char_length','character_length','check','close','coalesce','collate','collation','column','commit','connect','connection','constraint','constraints','continue','convert','corresponding','cross','current','current_date','current_time','current_timestamp','current_user','cursor','day','deallocate','declare','default','deferrable','deferred','desc','descending','describe','descriptor','diagnostics','disconnect','distinct','domain','else','end','end-exec','escape','except','exception','exec','execute','exists','external','extract','false','fetch','first','for','foreign','found','full','get','global','go','goto','grant','group','having','hour','identity','immediate','indicator','initially','inner','input','insensitive','intersect','isolation','join','key','language','last','leading','left','level','limit','local','lower','match','minute','module','month','names','national','natural','next','no','null','nullif','octet_length','of','only','open','option','or','order','outer','output','overlaps','pad','partial','position','precision','prepare','preserve','primary','prior','privileges','procedure','public','read','references','relative','restrict','revoke','right','rollback','rows','schema','scroll','second',/*'section',*/'session','session_user','size','some','space','sql','sqlcode','sqlerror','sqlstate','substring','system_user','table','temporary','then','timezone_hour','timezone_minute','to','trailing','transaction','translate','translation','trim','true','union','unique','unknown','upper','usage','user','using','value','values','varying','view','when','whenever','work','write','year','zone','eoc'),

'synonyms'=>array('decimal'=>'numeric','dec'=>'numeric','numeric'=>'numeric','float'=>'float','real'=>'real','double'=>'real','int'=>'int','integer'=>'int','interval'=>'interval','smallint'=>'smallint','timestamp'=>'timestamp','bool'=>'bool','boolean'=>'bool','set'=>'set','enum'=>'enum','text'=>'text','char'=>'char','character'=>'char','varchar'=>'varchar','ascending'=>'asc','asc'=>'asc','descending'=>'desc','desc'=>'desc','date'=>'date','time'=>'time'),

'units' => array('microsecond','second','minute','hour','day','week','month','quarter','year','second_microsecond','minute_microsecond','hour_microsecond','hour_second','hour_minute','day_microsecond','day_second','day_minute','day_hour','year_month'),

'quantifiers'=>array('all','distinct','some','any')
);
?>
