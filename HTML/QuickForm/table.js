
function editTableCell(e){var td=(e.target)?e.target:e.srcElement;while(td&&td.nodeName!="TD"){td=td.parentNode;}
if(td){var cVal=td.innerHTML;if(cVal.indexOf('_CellField')>0)return;var cWidth=parseInt(td.offsetWidth);var maxStr="_";for(var i=0;i<100;i++){maxStr+="_";}
td.innerHTML=maxStr;while(parseInt(td.offsetWidth)>cWidth){maxStr=maxStr.substr(1);td.innerHTML=maxStr;}
maxStr=maxStr.substr(1);td.innerHTML="<input type=text name='_CellField' maxlength="+maxStr.length+" value='"+cVal+"' style='width:"+cWidth+"'>";var iFld=td.childNodes[0];iFld.onblur=function(e){if(!e)e=event;setTableCellElement(e)};if(iFld.focus)iFld.focus();}}
function setTableCellElement(e){var iFld=(e.target)?e.target:e.srcElement;var cVal=iFld.value;var td=iFld.parentNode
td.innerHTML=cVal}
function setScriptText(script,text){try{script.innerHTML=text;}catch(e){script.text=text;}}
function getScriptText(script){if(script.innerHTML)return script.innerHTML;return script.text;}
function replaceRowIDs(el,ids,scripts,append,top){if(typeof(ids)=='undefined')ids={};if(typeof(scripts)=='undefined')scripts=[];if(el.hasChildNodes()){for(var i=0;i<el.childNodes.length;i++){if(el.childNodes[i].id){ids[el.childNodes[i].id]=el.childNodes[i].id+'-'+append;el.childNodes[i].id=el.childNodes[i].id+'-'+append;}
if(el.tagName=='SCRIPT'){scripts[scripts.length]=el;}
replaceRowIDs(el.childNodes[i],ids,scripts,append,false);}}
if(top){for(var i=0;i<scripts.length;i++){var script=document.createElement('script');script.type='text/javascript';var scrText=getScriptText(scripts[i]);for(var oldid in ids){scrText=scrText.replace(oldid,ids[oldid]);}
setScriptText(script,scrText);scripts[i].parentNode.replaceChild(script,scripts[i]);}}}
function insertNewTableRow(tableid){var table=document.getElementById(tableid);if(!table){return;}
var tbody=table.getElementsByTagName('tbody');if(tbody.length==0){return;}
tbody=tbody[0];rows=tbody.getElementsByTagName('tr');var prototype_row=null;for(i=0;i<rows.length;i++){if(rows[i].className=='prototype'){prototype_row=rows[i];break;}}
if(prototype_row==null){return;}
var new_row=prototype_row.cloneNode(true);var last_row=rows[rows.length-1];var name_pattern=/^([^\[]+)\[(\d+)\]\[([^\[]+)\]$/;var prototype_pattern=/^([^\[]+)\[prototype\]\[([^\[]+)\]$/;var new_cells=new_row.getElementsByTagName('td');if(last_row){var last_cells=last_row.getElementsByTagName('td');}else{var last_cells=new Array();}
if(new_cells.length!=last_cells.length){return;}
for(i=0;i<new_cells.length;i++){var new_input=new_cells[i].firstChild;var last_input=last_cells[i].firstChild;replaceRowIDs(new_cells[i],{},[],rows.length,true);var last_results=name_pattern.exec(last_input.getAttribute('name'));var new_results=prototype_pattern.exec(new_input.getAttribute('name'));if(last_results){var index=parseInt(last_results[2]);index++;new_input.setAttribute('name',last_results[1]+'['+index.toString()+']['+last_results[3]+']');}else{new_input.setAttribute('name',new_results[1]+'[0]['+new_results[2]+']');}
var delete_pattern=/__delete/;if(delete_pattern.exec(new_input.getAttribute('name'))){new_input.onclick=function(e){deleteRow(e);}}
new_row.className='';new_row.style.display='';}
tbody.appendChild(new_row);}
function deleteRow(e){var btn=(e.target)?e.target:e.srcElement;var row=btn;while(row){if(row.tagName.toLowerCase()=='tr'){break;}
row=row.parentNode;}
if(!row||row.tagName.toLowerCase()!='tr'){return;}
row.parentNode.removeChild(row);}