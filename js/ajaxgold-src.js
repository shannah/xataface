/*

                   Ajax Gold JavaScript Library
            ** No warranty is expressed or implied. **

  The Ajax Gold JavaScript Library executes in a web browser and allows 
  you to fetch data from the server behind the scenes, using JavaScript 
  without having the browser reload the current page (which would cause 
  the screen to flicker and reset while waiting for the page to be 
  loaded from the server), which is what Ajax is all about. This 
  library has been designed to be thread-safe.

  To use this JavaScript library in your own web pages, place 
  ajaxgold.js in the same directory as your web pages and use this 
  line in the <head> section of your pages:

  <script type = "text/javascript" src = "ajaxgold.js"></script>

  This library supports these functions for using Ajax (most commonly 
  used: getDataReturnText and getDataReturnXml):

  getDataReturnText(url, callback) 
    ** Uses the GET method to get text from the server. **
    Gets text from url, calls function named callback with that text.
    Use when you just want to get data from an URL, or can easily   
    encode the data you want to pass to the server in an URL, such as 
    "http://localhost/script.php?a=1&b=2&c=hello+there".
    Example: getDataReturnText("http://localhost/data.txt", doWork); 
    Here, the URL is a string, and doWork is a function in your own 
    script.

  getDataReturnXml(url, callback) 
    ** Uses the GET method to get XML from the server. **
    Gets XML from url, calls function named callback with that XML.
    Use when you just want to get data from an URL, or can easily   
    encode the data you want to pass to the server in an URL, such as 
    "http://localhost/script.php?a=1&b=2&c=hello+there".
    Example: getDataReturnXml("http://localhost/data.txt", doWork); 
    Here, the URL is a string, and doWork is a function in your 
    own script. You can recover XML elements from the XML object 
    passed to your callback function using JavaScript methods like 
    getElementsByTagName.

  postDataReturnText(url, data, callback) 
    ** Uses the POST method to send data to server, gets text back. **
    Posts data to url, calls function callback with the returned text.
    Uses the POST method, use this when you have more text data to send 
    to the server than can be easily encoded into an URL.
    Example: postDataReturnText("http://localhost/data.php", 
      "parameter=5", doWork); 
    Here, the URL is a string, the data sent to the server 
    ("parameter=5") is a string, and doWork is a function in 
    your own script.

  postDataReturnXml(url, data, callback) 
    ** Uses the POST method to send data to server, gets XML back. **
    Posts data to url, calls function callback with the returned XML.
    Uses the POST method, use this when you have more text data to send 
    to the server than can be easily encoded into an URL.
    Example: postDataReturnXml("http://localhost/data.php", 
      "parameter=5", doWork); 
    Here, the URL is a string, the data sent to the server 
    ("parameter=5") is a string, and doWork is a function in 
    your own script. You can recover XML elements from the XML object 
    passed to your callback function using JavaScript methods like 
    getElementsByTagName.

  Bear in mind that the URL you want to fetch data from has to be in
  the same domain as your web page that uses Ajax Gold methods or, as
  with any Ajax application, you'll get a security warning. If you want
  to fetch data from another domain, have your server-side program do 
  the fetching and send the fetched data back to your Ajax application.

*/

function getDataReturnText(url, callback)
{ 
  var XMLHttpRequestObject = false; 

  if (window.XMLHttpRequest) {
    XMLHttpRequestObject = new XMLHttpRequest();
  } else if (window.ActiveXObject) {
    XMLHttpRequestObject = new 
     ActiveXObject("Microsoft.XMLHTTP");
  }

  if(XMLHttpRequestObject) {
    XMLHttpRequestObject.open("GET", url); 

    XMLHttpRequestObject.onreadystatechange = function() 
    { 
      if (XMLHttpRequestObject.readyState == 4 && 
        XMLHttpRequestObject.status == 200) { 
          callback(XMLHttpRequestObject.responseText); 
          delete XMLHttpRequestObject;
          XMLHttpRequestObject = null;
      } 
    } 

    XMLHttpRequestObject.send(null); 
  }
}

function getDataReturnXml(url, callback)
{ 
  var XMLHttpRequestObject = false; 

  if (window.XMLHttpRequest) {
    XMLHttpRequestObject = new XMLHttpRequest();
  } else if (window.ActiveXObject) {
    XMLHttpRequestObject = new 
     ActiveXObject("Microsoft.XMLHTTP");
  }

  if(XMLHttpRequestObject) {
    XMLHttpRequestObject.open("GET", url); 

    XMLHttpRequestObject.onreadystatechange = function() 
    { 
      if (XMLHttpRequestObject.readyState == 4 && 
        XMLHttpRequestObject.status == 200) { 
          callback(XMLHttpRequestObject.responseXML); 
          delete XMLHttpRequestObject;
          XMLHttpRequestObject = null;
      } 
    } 

    XMLHttpRequestObject.send(null); 
  }
}

function postDataReturnText(url, data, callback)
{ 
  var XMLHttpRequestObject = false; 

  if (window.XMLHttpRequest) {
    XMLHttpRequestObject = new XMLHttpRequest();
  } else if (window.ActiveXObject) {
    XMLHttpRequestObject = new 
     ActiveXObject("Microsoft.XMLHTTP");
  }

  if(XMLHttpRequestObject) {
    XMLHttpRequestObject.open("POST", url); 
    XMLHttpRequestObject.setRequestHeader('Content-Type', 
      'application/x-www-form-urlencoded'); 

    XMLHttpRequestObject.onreadystatechange = function() 
    { 
      if (XMLHttpRequestObject.readyState == 4 && 
        XMLHttpRequestObject.status == 200) {
          callback(XMLHttpRequestObject.responseText); 
          delete XMLHttpRequestObject;
          XMLHttpRequestObject = null;
      } 
    }

    XMLHttpRequestObject.send(data); 
  }
}

function postDataReturnXml(url, data, callback)
{ 
  var XMLHttpRequestObject = false; 

  if (window.XMLHttpRequest) {
    XMLHttpRequestObject = new XMLHttpRequest();
  } else if (window.ActiveXObject) {
    XMLHttpRequestObject = new 
     ActiveXObject("Microsoft.XMLHTTP");
  }

  if(XMLHttpRequestObject) {
    XMLHttpRequestObject.open("POST", url); 
    XMLHttpRequestObject.setRequestHeader('Content-Type', 
      'application/x-www-form-urlencoded'); 

    XMLHttpRequestObject.onreadystatechange = function() 
    { 
      if (XMLHttpRequestObject.readyState == 4 && 
        XMLHttpRequestObject.status == 200) {
          callback(XMLHttpRequestObject.responseXML); 
          delete XMLHttpRequestObject;
          XMLHttpRequestObject = null;
      } 
    }

    XMLHttpRequestObject.send(data); 
  }
}


