
var request;function getHTTPObject(){try{request=new XMLHttpRequest();}catch(trymicrosoft){try{request=new ActiveXObject("Msxml2.XMLHTTP");}catch(othermicrosoft){try{request=new ActiveXObject("Microsoft.XMLHTTP");}catch(failed){request=false;}}}
if(!request)
alert("Error initializing XMLHttpRequest!");return request;}
function getHTTPObject_old(){var xmlhttp;if(!xmlhttp&&typeof XMLHttpRequest!='undefined'){try{xmlhttp=new XMLHttpRequest();}catch(e){xmlhttp=false;}}
return xmlhttp;}
