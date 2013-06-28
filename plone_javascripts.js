if ( typeof(window.console)=='undefined' ){
	window.console = {
		log: function(str){}
	};
}
function registerPloneFunction(func){if(window.addEventListener)window.addEventListener("load",func,false);else if(window.attachEvent)window.attachEvent("onload",func);}
function unRegisterPloneFunction(func){if(window.removeEventListener)window.removeEventListener("load",func,false);else if(window.detachEvent)window.detachEvent("onload",func);}
function getContentArea(){node=document.getElementById('region-content')
if(!node){node=document.getElementById('content')}
return node}
function wrapNode(node,wrappertype,wrapperclass){wrapper=document.createElement(wrappertype)
wrapper.className=wrapperclass;innerNode=node.parentNode.replaceChild(wrapper,node);wrapper.appendChild(innerNode)}
var registerXatafaceDecorator=null;var decorateXatafaceNode=null;(function(){var decorators=[];registerXatafaceDecorator=function(decorator){decorators.push(decorator);};decorateXatafaceNode=function(node){var replaceCallbacks=[];removeNoDecorateSections(node,replaceCallbacks);for(var i=0;i<decorators.length;i++){decorators[i](node);}
for(var i=0;i<replaceCallbacks.length;i++){replaceCallbacks[i]();}}
function removeNoDecorateSections(node,callbacks){if(typeof(jQuery)!='undefined'){jQuery('.xf-disable-decorate',node).each(function(){var replace=document.createTextNode('');var parent=jQuery(this).parent();jQuery(this).replaceWith(replace);var self=this;callbacks.push(function(){jQuery(replace).replaceWith(self);});});}}})();registerPloneFunction(function(){decorateXatafaceNode(document.documentElement)});function showDay(date){document.getElementById('day'+date).style.visibility='visible';return true;}
function hideDay(date){document.getElementById('day'+date).style.visibility='hidden';return true;}
function setFocus(){var xre=new RegExp(/\berror\b/);for(var f=0;(formnode=document.getElementsByTagName('form').item(f));f++){for(var i=0;(node=formnode.getElementsByTagName('div').item(i));i++){if(xre.exec(node.className)){for(var j=0;(inputnode=node.getElementsByTagName('input').item(j));j++){inputnode.focus();return;}}}}}
registerPloneFunction(setFocus)
function compare(a,b)
{au=new String(a);bu=new String(b);if(au.charAt(4)!='-'&&au.charAt(7)!='-')
{var an=parseFloat(au)
var bn=parseFloat(bu)}
if(isNaN(an)||isNaN(bn))
{as=au.toLowerCase()
bs=bu.toLowerCase()
if(as>bs)
{return 1;}
else
{return-1;}}
else{return an-bn;}}
function getConcatenedTextContent(node){var _result="";if(node==null){return _result;}
var childrens=node.childNodes;var i=0;while(i<childrens.length){var child=childrens.item(i);switch(child.nodeType){case 1:case 5:_result+=getConcatenedTextContent(child);break;case 3:case 2:case 4:_result+=child.nodeValue;break;case 6:case 7:case 8:case 9:case 10:case 11:case 12:break;}
i++;}
return _result;}
function sort(e){var el=window.event?window.event.srcElement:e.currentTarget;var a=new Array();if(el.nodeName=='IMG')el=el.parentNode;var name=el.childNodes.item(1).nodeValue;var dad=el.parentNode;var node;for(var im=0;(node=dad.getElementsByTagName("th").item(im));im++){if(node.lastChild.nodeName=='IMG')
{lastindex=node.getElementsByTagName('img').length-1;node.getElementsByTagName('img').item(lastindex).setAttribute('src',portal_url+'/images/arrowBlank.gif');}}
for(var i=0;(node=dad.getElementsByTagName("th").item(i));i++){var xre=new RegExp(/\bnosort\b/);if(!xre.exec(node.className)&&node.childNodes.item(1).nodeValue==name)
{lastindex=node.getElementsByTagName('img').length-1;node.getElementsByTagName('img').item(lastindex).setAttribute('src',portal_url+'/images/arrowUp.gif');break;}}
var tbody=dad.parentNode.parentNode.getElementsByTagName("tbody").item(0);for(var j=0;(node=tbody.getElementsByTagName("tr").item(j));j++){a[j]=new Array();a[j][0]=getConcatenedTextContent(node.getElementsByTagName("td").item(i));a[j][1]=getConcatenedTextContent(node.getElementsByTagName("td").item(1));a[j][2]=getConcatenedTextContent(node.getElementsByTagName("td").item(0));a[j][3]=node;}
if(a.length>1){a.sort(compare);if(a[0][0]==getConcatenedTextContent(tbody.getElementsByTagName("tr").item(0).getElementsByTagName("td").item(i))&&a[1][0]==getConcatenedTextContent(tbody.getElementsByTagName("tr").item(1).getElementsByTagName("td").item(i)))
{a.reverse();lastindex=el.getElementsByTagName('img').length-1;el.getElementsByTagName('img').item(lastindex).setAttribute('src',portal_url+'/images/arrowDown.gif');}}
for(var j=0;j<a.length;j++){tbody.appendChild(a[j][3]);}}
function initalizeTableSort(e){var tbls=document.getElementsByTagName('table');for(var t=0;t<tbls.length;t++)
{var re=new RegExp(/\blisting2\b/)
var xre=new RegExp(/\bnosort\b/)
if(re.exec(tbls[t].className)&&!xre.exec(tbls[t].className))
{try{var tablename=tbls[t].getAttribute('id');var thead=document.getElementById(tablename).getElementsByTagName("thead").item(0);var node;blankarrow=document.createElement('img');blankarrow.setAttribute('src',portal_url+'/images/arrowBlank.gif');blankarrow.setAttribute('height',6);blankarrow.setAttribute('width',9);initialsort=false;for(var i=0;(node=thead.getElementsByTagName("th").item(i));i++){if(!xre.exec(node.className)){node.insertBefore(blankarrow.cloneNode(1),node.firstChild);if(!initialsort){initialsort=true;uparrow=document.createElement('img');uparrow.setAttribute('src',portal_url+'/images/arrowUp.gif');uparrow.setAttribute('height',6);uparrow.setAttribute('width',9);node.appendChild(uparrow);}else{node.appendChild(blankarrow.cloneNode(1));}
if(node.addEventListener)node.addEventListener("click",sort,false);else if(node.attachEvent)node.attachEvent("onclick",sort);}}}catch(er){}}}}
registerPloneFunction(initalizeTableSort)
function submitFolderAction(folderAction){document.folderContentsForm.action=document.folderContentsForm.action+'/'+folderAction;document.folderContentsForm.submit();}
function submitFilterAction(){document.folderContentsForm.action=document.folderContentsForm.action+'/folder_contents';filter_selection=document.getElementById('filter_selection');for(var i=0;i<filter_selection.length;i++){if(filter_selection.options[i].selected){if(filter_selection.options[i].value=='#'){document.folderContentsForm.filter_state.value='clear_view_filter';}
else{document.folderContentsForm.filter_state.value='set_view_filter';}}}
document.folderContentsForm.submit();}
function selectAll(id,formName){if(formName==null){checkboxes=document.getElementsByName(id)
for(i=0;i<checkboxes.length;i++)
checkboxes[i].checked=true;}else{for(i=0;i<document.forms[formName].elements.length;i++)
{if(document.forms[formName].elements[i].name==id)
document.forms[formName].elements[i].checked=true;}}}
function deselectAll(id,formName){if(formName==null){checkboxes=document.getElementsByName(id)
for(i=0;i<checkboxes.length;i++)
checkboxes[i].checked=false;}else{for(i=0;i<document.forms[formName].elements.length;i++)
{if(document.forms[formName].elements[i].name==id)
document.forms[formName].elements[i].checked=false;}}}
function toggleSelect(selectbutton,id,initialState,formName){id=id||'ids:list'
if(selectbutton.isSelected==null)
{initialState=initialState||false;selectbutton.isSelected=initialState;}
if(selectbutton.isSelected==false){selectbutton.setAttribute('src',portal_url+'/images/select_none_icon.gif');selectbutton.isSelected=true;return selectAll(id,formName);}
else{selectbutton.setAttribute('src',portal_url+'/images/select_all_icon.gif');selectbutton.isSelected=false;return deselectAll(id,formName);}}
function scanforlinks(){if(!document.getElementsByTagName){return false};if(!document.getElementById){return false};contentarea=getContentArea()
if(!contentarea){return false}
links=contentarea.getElementsByTagName('a');for(i=0;i<links.length;i++){if((links[i].getAttribute('href'))&&(links[i].className.indexOf('link-plain')==-1)){var linkval=links[i].getAttribute('href')
if(linkval.toLowerCase().indexOf(window.location.protocol+'//'+window.location.host)==0){}else if(linkval.indexOf('http:')!=0){protocols=['mailto','ftp','news','irc','h323','sip','callto','https']
for(p=0;p<protocols.length;p++){if(linkval.indexOf(protocols[p]+':')==0){wrapNode(links[i],'span','link-'+protocols[p])
break;}}}else{if(links[i].getElementsByTagName('img').length==0&&!links[i].className.match(/no-link-icon/)){wrapNode(links[i],'span','link-external')}}}}}
registerPloneFunction(scanforlinks)
function climb(node,word){if(!node){return false}
if(node.hasChildNodes){var i;for(i=0;i<node.childNodes.length;i++){climb(node.childNodes[i],word);}
if(node.nodeType==3){checkforhighlight(node,word);}}
function checkforhighlight(node,word){ind=node.nodeValue.toLowerCase().indexOf(word.toLowerCase())
if(ind!=-1){if(node.parentNode.className!="highlightedSearchTerm"){par=node.parentNode;contents=node.nodeValue;hiword=document.createElement("span");hiword.className="highlightedSearchTerm";hiword.appendChild(document.createTextNode(contents.substr(ind,word.length)));par.insertBefore(document.createTextNode(contents.substr(0,ind)),node);par.insertBefore(hiword,node);par.insertBefore(document.createTextNode(contents.substr(ind+word.length)),node);par.removeChild(node);}}}}
function correctPREformatting(){contentarea=getContentArea();if(!contentarea){return false}
pres=contentarea.getElementsByTagName('pre');for(i=0;i<pres.length;i++){wrapNode(pres[i],'div','visualOverflow')}}
function highlightSearchTerm(){query=window.location.search
if(typeof decodeURI!='undefined'){query=unescape(decodeURI(query))}
else{return false}
if(query){var qfinder=new RegExp()
qfinder.compile("searchterm=([^&]*)","gi")
qq=qfinder.exec(query)
if(qq&&qq[1]){query=qq[1]
if(!query){return false}
queries=query.replace(/\+/g,' ').split(/\s+/)
contentarea=getContentArea();for(q=0;q<queries.length;q++){if(queries[q].toLowerCase()!='not'&&queries[q].toLowerCase()!='and'&&queries[q].toLowerCase()!='or'){climb(contentarea,queries[q]);}}}}}
registerPloneFunction(highlightSearchTerm);function setActiveStyleSheet(title,reset){var i,a,main;for(i=0;(a=document.getElementsByTagName("link")[i]);i++){if(a.getAttribute("rel").indexOf("style")!=-1&&a.getAttribute("title")){a.disabled=true;if(a.getAttribute("title")==title)a.disabled=false;}}
if(reset==1){createCookie("wstyle",title,365);}}
function setStyle(){var style=readCookie("wstyle");if(style!=null){setActiveStyleSheet(style,0);}}
function createCookie(name,value,days){if(days){var date=new Date();date.setTime(date.getTime()+(days*24*60*60*1000));var expires="; expires="+date.toGMTString();}
else expires="";document.cookie=name+"="+escape(value)+expires+"; path=/;";}
function readCookie(name){var nameEQ=name+"=";var ca=document.cookie.split(';');for(var i=0;i<ca.length;i++){var c=ca[i];while(c.charAt(0)==' ')c=c.substring(1,c.length);if(c.indexOf(nameEQ)==0)return unescape(c.substring(nameEQ.length,c.length));}
return null;}
registerPloneFunction(setStyle);function onJsCalendarDateUpdate(cal){var year=cal.params.input_id_year;var month=cal.params.input_id_month;var day=cal.params.input_id_day;var daystr=''+cal.date.getDate();if(daystr.length==1)
daystr='0'+daystr;var monthstr=''+(cal.date.getMonth()+1);if(monthstr.length==1)
monthstr='0'+monthstr;cal.params.inputField.value=''+cal.date.getFullYear()+'/'+monthstr+'/'+daystr
year.value=cal.params.inputField.value.substring(0,4);month.value=cal.params.inputField.value.substring(5,7);day.value=cal.params.inputField.value.substring(8,10);}
function showJsCalendar(input_id_anchor,input_id,input_id_year,input_id_month,input_id_day,input_id_hour,input_id_minute,yearStart,yearEnd){var input_id_anchor=document.getElementById(input_id_anchor);var input_id=document.getElementById(input_id);var input_id_year=document.getElementById(input_id_year);var input_id_month=document.getElementById(input_id_month);var input_id_day=document.getElementById(input_id_day);var format='y/mm/dd';var dateEl=input_id;var mustCreate=false;var cal=window.calendar;var params={'range':[yearStart,yearEnd],inputField:input_id,input_id_year:input_id_year,input_id_month:input_id_month,input_id_day:input_id_day};function param_default(pname,def){if(typeof params[pname]=="undefined"){params[pname]=def;}};param_default("inputField",null);param_default("displayArea",null);param_default("button",null);param_default("eventName","click");param_default("ifFormat","%Y/%m/%d");param_default("daFormat","%Y/%m/%d");param_default("singleClick",true);param_default("disableFunc",null);param_default("dateStatusFunc",params["disableFunc"]);param_default("mondayFirst",true);param_default("align","Bl");param_default("range",[1900,2999]);param_default("weekNumbers",true);param_default("flat",null);param_default("flatCallback",null);param_default("onSelect",null);param_default("onClose",null);param_default("onUpdate",null);param_default("date",null);param_default("showsTime",false);param_default("timeFormat","24");if(!window.calendar){window.calendar=cal=new Calendar(true,null,onJsCalendarDateUpdate,function(cal){cal.hide();});cal.time24=true;cal.weekNumbers=true;mustCreate=true;}else{cal.hide();}
cal.setRange(yearStart,yearEnd);cal.params=params;cal.setDateStatusHandler(null);cal.setDateFormat(format);if(mustCreate)
cal.create();cal.parseDate(dateEl.value||dateEl.innerHTML);cal.refresh();cal.showAtElement(input_id_anchor,null);return false;}
function update_date_field(field,year,month,day,hour,minute,ampm)
{var field=document.getElementById(field)
var date=document.getElementById(date)
var year=document.getElementById(year)
var month=document.getElementById(month)
var day=document.getElementById(day)
var hour=document.getElementById(hour)
var minute=document.getElementById(minute)
var ampm=document.getElementById(ampm)
if(0<year.value)
{field.value=year.value+"-"+month.value+"-"+day.value+" "+hour.value+":"+minute.value
if(ampm&&ampm.value)
field.value=field.value+" "+ampm.value}
else
{field.value=''
month.options[0].selected=1
day.options[0].selected=1
hour.options[0].selected=1
minute.options[0].selected=1
if(ampm&&ampm.options)
ampm.options[0].selected=1}}
function fullscreenMode(){if(document.getElementById('portal-top').style.display=='none'){document.getElementById('portal-top').style.display='block';document.getElementById('portal-column-one').style.display='block';document.getElementById('portal-column-two').style.display='block';}
else{document.getElementById('portal-top').style.display='none';document.getElementById('portal-column-one').style.display='none';document.getElementById('portal-column-two').style.display='none';}}
function invalidateTranslations(url){var res=confirm('Are you sure you want to invalidate the translations for this record?  This will mark the record for re-translation.');if(!res)return;var div=document.getElementsByTagName('body')[0].appendChild(document.createElement('div'));var html='<form id="invalidate_translation_form" method="POST" action="'+url+'">';html+='<input type="hidden" name="--confirm_invalidate" value="1">';div.innerHTML=html;var form=document.getElementById('invalidate_translation_form');form.submit();}
function hackPush(el){this[this.length]=el;}
function hackPop(){var N=this.length-1,el=this[N];this.length=N
return el;}
function hackShift(){var one=this[0],N=this.length;for(var i=1;i<N;i++){this[i-1]=this[i];}
this.length=N-1
return one;}
function require(path){if(!window._javascripts_loaded)window._javascripts_loaded={};if(window._javascripts_loaded[path])return true;else window._javascripts_loaded[path]=true;var e=document.createElement("script");e.src=path;e.type="text/javascript";document.getElementsByTagName("head")[0].appendChild(e);}
function loadScripts(e){var scriptTags=e.getElementsByTagName('script');for(var i=0;i<scriptTags.length;i++){if(scriptTags[i].getAttribute('src'))require(scriptTags[i].getAttribute('src'));}}
function registerRecord(id,vals){if(!document.recordIndex)document.recordIndex={};document.recordIndex[id]=vals;}
function getRecord(id){if(!document.recordIndex)document.recordIndex={};return document.recordIndex[id];}
function addToValuelist(table,valuelist,element){var value=prompt('Enter the value you wish to add to this value list.  Use the notation key=value if you need to add both a key and a value for the option.');if(!value)return;if(value.indexOf('=')>=0){var vals=value.split('=');var key=vals[0];value=vals[1];}else{key=null;}
var http=getHTTPObject();http.open('POST',window.location,true);var params="-action=ajax_valuelist_append&-table="+escape(table)+"&-valuelist="+escape(valuelist)+"&-value="+escape(value)+"&-key="+escape(key);http.setRequestHeader("Content-type","application/x-www-form-urlencoded");http.setRequestHeader("Content-length",params.length);http.setRequestHeader("Connection","close");http.element=element;http.onreadystatechange=function(){if(http.readyState==4){eval('var retval = '+http.responseText+';');if(retval['success']){element.options[element.options.length]=element.options[element.options.length-1];element.options[element.options.length-2]=new Option(retval['value']['value'],retval['value']['key']);element.selectedIndex=element.options.length-2;}else{alert(retval['msg']);element.selectedIndex=0;}}}
http.send(params);}
function makeSelectEditable(table,valuelist,select){if(select.onchange){select.onchange_old=select.onchange;}
select.onchange=function(){if(this.options[this.selectedIndex].value=='-1'){addToValuelist(table,valuelist,this);}
if(this.onchange_old)
return this.onchange_old();};select.options[select.options.length]=new Option('Edit values...','-1');}
var testPushPop=new Array();if(testPushPop.push){}else{Array.prototype.push=hackPush
Array.prototype.pop=hackPop
Array.prototype.shift=hackShift;}
function registerOnloadHandler(func){if(!document._onload)document._onload=[];document._onload[document._onload.length]=func;}
function bodyOnload(){if(document._onload){for(var i=0;i<document._onload.length;i++){document._onload[i]();}}}
function getElementsByClassName(oElm,strTagName,strClassName){var arrElements=(strTagName=="*"&&oElm.all)?oElm.all:oElm.getElementsByTagName(strTagName);var arrReturnElements=new Array();strClassName=strClassName.replace(/\-/g,"\\-");var oRegExp=new RegExp("(^|\\s)"+strClassName+"(\\s|$)");var oElement;for(var i=0;i<arrElements.length;i++){oElement=arrElements[i];if(oRegExp.test(oElement.className)){arrReturnElements.push(oElement);}}
return(arrReturnElements)}
function toggleSelectedRows(checkbox,tableid){var table=document.getElementById(tableid);var checkboxes=getElementsByClassName(table,'input','rowSelectorCheckbox');for(var i=0;i<checkboxes.length;i++){checkboxes[i].checked=checkbox.checked;}}
function getSelectedIds(tableid){var table=document.getElementById(tableid);var checkboxes=getElementsByClassName(table,'input','rowSelectorCheckbox');var ids=[];for(var i=0;i<checkboxes.length;i++){if(checkboxes[i].checked){var id=checkboxes[i].getAttribute('id');id=id.substring(id.indexOf(':')+1);ids.push(id);}}
return ids;}
function actOnSelected(tableid,action,beforeHook,vals){var ids=getSelectedIds(tableid);if(ids.length==0){alert("First you must select the rows you wish to modify.");return false;}
if(typeof(beforeHook)!='undefined'){if(!beforeHook())return false;}
var form=document.getElementById("result_list_selected_items_form");form.elements['--selected-ids'].value=ids.join("\n");form.elements['-action'].value=action;form.submit();return false;}
function copySelected(tableid){var ids=getSelectedIds(tableid);if(ids.length==0){alert("Please first check boxes beside the records you wish to copy, and then press 'Copy'.");return;}
var form=document.getElementById("result_list_selected_items_form");form.elements['--selected-ids'].value=ids.join("\n");form.elements['-action'].value='copy_replace';var fld=document.createElement('input');fld.name='--copy';fld.type='hidden';fld.value='1';form.appendChild(fld);form.submit();}
function updateSelected(tableid){var ids=getSelectedIds(tableid);if(ids.length==0){alert("Please first check boxes beside the records you wish to update, and then press 'Update'.");return;}
var form=document.getElementById("result_list_selected_items_form");form.elements['--selected-ids'].value=ids.join("\n");form.elements['-action'].value='copy_replace';form.submit();}
function removeSelectedRelated(tableid){var ids=getSelectedIds(tableid);if(ids.length==0){alert("Please first check boxes beside the records you wish to remove, and then press 'Remove'.");return;}
var form=document.getElementById("result_list_selected_items_form");form.elements['--selected-ids'].value=ids.join("\n");form.elements['-action'].value='remove_related_record';form.submit();}