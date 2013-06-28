var request;
function getHTTPObject() {
   //alert('here');
   //var request;
  try {
    request = new XMLHttpRequest();
  } catch (trymicrosoft) {
    try {
      request = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (othermicrosoft) {
      try {
        request = new ActiveXObject("Microsoft.XMLHTTP");
      } catch (failed) {
        request = false;
      }
    }
  }

  if (!request)
    alert("Error initializing XMLHttpRequest!");
  //else 
  //  alert("Request is "+request);
    
  return request;
}


/* Function copied from http://www.webpasties.com/xmlHttpRequest/xmlHttpRequest_tutorial_1.html */
function getHTTPObject_old() {

  var xmlhttp;

  /*@cc_on

  @if (@_jscript_version >= 5)

    try {

      xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");

    } catch (e) {

      try {

        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");

      } catch (E) {

        xmlhttp = false;

      }

    }

  @else

  xmlhttp = false;

  @end @*/

  if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {

    try {

      xmlhttp = new XMLHttpRequest();

    } catch (e) {

      xmlhttp = false;

    }

  }

  return xmlhttp;

}


