<?php

/*******************************************************************************
** Class: GoogleTranslate
** Purpose: Translate text using Google language tools
** Filename: GoogleTranslate.class.php
** Author: Raymond Mancy
** Author Email: raymond . mancy @ gmail . com
** Date: June 3 2006
**
********************************************************************************/

/* if ! PHP5 */
if (!function_exists('http_build_query')) {
   function http_build_query($formdata, $numeric_prefix = "")
   {
       $arr = array();
       foreach ($formdata as $key => $val)
         $arr[] = urlencode($numeric_prefix.$key)."=".urlencode($val);
       return implode($arr, "&");
   }
}

class GoogleTranslate{

  var $languages = NUll; //array to store valid languages
  
  var $validTranslation = NULL; //array to store valid lang-lang translation

  var $langFrom = NULL; //language converting from
  
  var $langTo = NULL;  //language conveting to
  
  var $webPage = false;
  
  var $text = NULL; //text to translate
  
  var $google_url = NULL; //where we get the service from
  
  var $google_url_webpage = NULL; // where we get the service from for web page translations
  
  var $post_data = NULL;
  
function GoogleTranslate($langFrom=null, $langTo=null, $text=null, $webPage=null) {


  $this->google_url = "http://translate.google.com/translate_t";
  $this->google_url_webpage = "http://translate.google.com/translate_c";
  
  $this->langFrom = $langFrom;
  $this->langTo = $langTo;
  $this->webPage = $webPage;
  $this->text = $text; //text to translate to


$this->languages= array ( //elligable languages (I've only included common Euro langs)
             "en" => "english",
             "ar" => "arabic",
             "de" => "german",
             "fr" => "french",
             "es" => "spanish",
             "it"=>  "italian",
             "pt"=> "portugese",
             "ko"=> "korean",
             "zh-CN" =>"chinese simplified",
             "ja"
             );

$this->validTranslations =  array ( //these are the conversions allowed (I've only included
                     //common Euro languages
                    

                    "en|de", //English to German
                    "en|fr", //English to French
                    "en|it", //English to Italian
                    "en|es", //English to Spanish
                    "en|pt", //English to Portugese
                    "en|ar", //English to Arabic
                    "en|ko", //English to Korean
                    "en|ja", // English to japanese
                    "en|zh-CN", //English to Chinese simplified
                    
                    "de|en", //German to English
                    "de|fr", //German to French
                    
                    "fr|en", //French to English
                    "fr|de", //French to German

                    "it|en", //Italian to English
                    
                    "es|en", //Spanish to English
                    
                    "pt|en",
                    "ar|en",
                    "ko|en",
                    "zh-CN|en",
                    "ja|en"
                    
                );
  $post_data = array(
             'langpair'=> NULL,
             'text' => NULL
             );





} //constructor
    



/*returnLanguageTo() will return the lanaguage that has currently been set
  *to translate TO
  *preconditions:- contructor has been called
  *postconditions:- none
  *returns: - the language that has been set to translate to, and null if none have been set
  */
function returnLanguageTo() {

   return $this->langTo;
}

  /*returnLanguageFrom() will return the lanaguage that has currently been set
  *to translate FROM
  *preconditions:- contructor has been called
  *postconditions:- none
  *returns: - the language that has been set to translate FROM, and null if none have been set
  */
function returnLanguageFrom() {

   return $this->langFrom;

}






  /*setLanguageTo() will set the lanaguage to translate TO
  *
  *preconditions:- none
  *postconditions:- the language to be translated to will be set to $langT
  *returns: - returns -1 on failure if the language is not valid, else 1
  */
function setLanguageTo($langT) {


   //need to implemetn language checking
   $this->langTo = $langT;







}




  /*setLanguageFrom() will set the lanaguage to translate FROM
  *
  *preconditions:- none
  *postconditions:- the language to be translated FROM will be set to $langF
  *returns: - returns -1 on failure if the language is not valid, else 1
  */
//set the language to translate from
function setLanguageFrom($langF) {

   
   //need to implement language checking
     $this->langFrom = $langF;



     



}

function setWebPage($page){
	$this->webPage = $page;
}

/*setText() sets the text to be translated
  *preconditions: none
  *postconditions: sets the text to be translated to $text
  *returns: n/a
  */

function setText($text) {

   $this->text = $text;

}


function validateLang($langPair) {

   return 1;
  if (!in_array($langPair,$this->validTranslations)) {
    return -1;
  }

   
   return 1;
}




/*translate will take the language from, language to, and text variable already initialised
  * and return the translated text
  *preconditions:- $this->languageFrom, $this->languageTo, $this->text have already been
  *initialised
  *postcoditions:-none
  *returns:- the translated text
  */
function translate($text=null, $languageFrom=null, $languageTo=null, $webPage=null) {
   if ( isset($text) ) $this->setText($text);
   if ( isset($languageFrom) ) $this->setLanguageFrom($languageFrom);
   if ( isset($languageTo) ) $this->setLanguageTo($languageTo);
   if ( isset($webPage) ) $this->setWebPage($webPage);
   $i = $this->langFrom."|".$this->langTo;
   if ($this->validateLang($i) < 0 )
     die ("Error, could not translate");
   
   //echo "Setting langpair to ". $i;
   $this->post_data['langpair'] = $i;
   
   $query = $this->buildQuery();
   
   //echo "Build query created this  ".$query;
   $ch = curl_init();
   $this_header = array(
		   //"MIME-Version: 1.0",
		   'Accept-Charset: utf-8'
		);
   curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
   curl_setopt($ch,CURLOPT_COOKIEJAR,"cookie");
   curl_setopt($ch,CURLOPT_COOKIEFILE,"cookie");
   if ( $this->webPage ){
   		curl_setopt($ch, CURLOPT_URL, $this->google_url_webpage);
   		 curl_setopt($ch, CURLOPT_POST, 1);
   } else {
   		curl_setopt($ch, CURLOPT_URL, $this->google_url);
   		 curl_setopt($ch, CURLOPT_POST, 1);
   }
   
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   
  
   curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
   //echo $query;
   curl_setopt($ch, CURLOPT_HTTPHEADER, $this_header);
   curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; U; PPC Mac OS X Mach-O; en-US; rv:1.8.1a2) Gecko/20060512 BonEcho/2.0a2');


   $output = curl_exec($ch);
   
   $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
   if ( $responseCode == 502 ){
   	   return PEAR::raiseError("Service has likely been blocked", CURLINFO_HTTP_CODE);
   }
    if ( !$output ){
   		$errmsg = curl_error($ch);
   		//echo $errmsg;
   		return PEAR::raiseError('No content returned: '.$errmsg);
   	}
  
   
   $processedOutput = $this->filterOutput($output);
   
   return $processedOutput;

   
} //translate()

/*processOutput() takes the source page of a request to google translate and filters it for
  * the translated words
  *preconditions:- $output is valid source from googles translate service
  *postconditions:- none
  *returns:- the translated words we are looking for
  */

function filterOutput($output) {
   

   if ( $this->webPage ){
   		$search_regex = '/<div id="content_to_be_translated"[^>]*>(.*)<\/div>/s';
   	   $result = preg_match($search_regex,$output,$match);
   	   if ( !$result ) {
   	   		//echo $output;
   	   		return PEAR::raiseError('Regex not found in translated content', E_USER_ERROR);
   	   		//exit;
   	   	}
   	   	
   	   return $match[1];
   	
   } else {
	   $search_regex='/<textarea name=q rows=5 cols=45 wrap=PHYSICAL dir=ltr>(.*)<\/textarea>\&nbsp;\&nbsp;<input type=hidden/';
	   //echo $output;
	   
	   $result = preg_match($search_regex,$output,$match);
	   $match[0] = str_replace("&nbsp;","",$match[0]); //this gets rid of the HTML no break spaces
	   $out = strip_tags($match[0]);
	   return html_entity_decode($out, ENT_COMPAT, 'UTF-8');
   }
   
} //filterOutput()



/*buildQuery() will take the already initialised values of languageFrom, languageTo and text
  *to be translated, and puts them into a format which can be submitted as a request
  *to the google translating service
  */
function buildQuery() {

   if (($this->langTo || $this->langFrom || $this-> text) == ("" || NULL))
     die("Please set language to, language from, and the text to be translated");
   

  
   if ( $this->webPage ) {
        unset($this->post_data['text']);
   	    $this->post_data['u'] = $this->text; // set the url for the page to translate
   	    			// When the webPage flag is set, the text is presumed to be 
   	    			// a url that is to be translated.
   } else {
   		unset($this->post_data['u']);
   		$this->post_data['text']= $this->text; //set the text of what we want to translate
   }
   $this->post_data['ie'] = 'UTF8';
   $this->post_data['oe'] = 'UTF8';
   
   
   return http_build_query($this->post_data); //create proper HTML query

}//buildQuery()




   



} //GoogleTranslate
?>
