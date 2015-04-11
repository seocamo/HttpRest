<?php
/***************************************************************************\
The MIT License (MIT)

Copyright (c) 2015 Peter Bryrup

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
\***************************************************************************/

// HttpRest - class
class HttpRest {
   private $_Config;
   private $_Routes;

   function __construct($pConfig) {
      $this->_Config = $pConfig;
      $this->_Routes = array(
         'GET' => array(),
         'PUT' => array(),
         'POST' => array(),
         'DELETE' => array()
         );
   }

   public static function Res($pArray) {
      header('Content-type: application/json');
      echo json_encode($pArray);

      switch (json_last_error()) {
         case JSON_ERROR_NONE:
         /* No errors */
         break;
         case JSON_ERROR_DEPTH:
         echo ' - Maximum stack depth exceeded';
         break;
         case JSON_ERROR_STATE_MISMATCH:
         echo ' - Underflow or the modes mismatch';
         break;
         case JSON_ERROR_CTRL_CHAR:
         echo ' - Unexpected control character found';
         break;
         case JSON_ERROR_SYNTAX:
         echo ' - Syntax error, malformed JSON';
         break;
         case JSON_ERROR_UTF8:
         echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
         break;
         default:
         echo ' - Unknown error';
         break;
      }     
      exit();
   }
   private function Req2Array($pFile){
      $pFile = basename($pFile);
      $pos = strpos(strtolower($_SERVER["REQUEST_URI"]), strtolower($pFile));
      $str = substr($_SERVER["REQUEST_URI"], $pos + strlen($pFile));
      return $this->Url2Array($str);
   }
   
   private function Url2Array($pStr){
      return explode('/', $pStr);
   }

   private function GetRouteData($pReqArray) {

      $lRoutes = $this->_Routes[$_SERVER['REQUEST_METHOD']];
      $lRouteData = null;
      while (@list(,$lRoute) = @each($lRoutes)) {
         $lUrl2Array = $this->Url2Array($lRoute['Url']);

         if(count($pReqArray) == count($lUrl2Array)) {
            $lFound = true;
            $lData = array();

            for ($i = 0; $i < count($lUrl2Array); $i++) {

               $lFirstChar = (empty($lUrl2Array[$i])) ? " " : $lUrl2Array[$i][0];

               $lIsEmpty = empty($lUrl2Array[$i]);
               $lIsString = ($lFirstChar == ":");
               $lIsNumeric = ($lFirstChar == "#");
               $lIsConst = (!$lIsEmpty && !$lIsString && !$lIsNumeric);

               if( $lIsEmpty && empty($pReqArray[$i]) ) {
                  continue;
               } else if( $lIsString ) {
                  $lData[substr($lUrl2Array[$i], 1)] = $pReqArray[$i];
                  continue;
               } else if( $lIsNumeric && is_numeric($pReqArray[$i]) ) {
                  $lData[substr($lUrl2Array[$i], 1)] = $pReqArray[$i];
                  continue;
               } else if( $lIsConst && !empty($pReqArray[$i]) ) {
                  continue;
               } else {
                  $lFound = false;
                  break;
               }
            }
            if( $lFound ) {
               $lSelectedRoute = $lRoute;
               return $this->MakeRouteData($pReqArray, $lRoute, $lData);
            } else {
               $lData = array();
            }
         }
      }
      App::Res(array('error' => 'No Route!!!'));
   }
   
   private function MakeRouteData($pReqArray, $pRoute, $pData)
   {

      return array("Param" => $pData,
         'CallBack' => $pRoute['CallBack']
         );
   }

   public function Get($pUrl, $pCallBack) {
      $this->_Routes['GET'][] = array(
         'Url' => $pUrl, 
         'CallBack' => $pCallBack
         );
   }

   public function Put($pUrl, $pCallBack) {
      $this->_Routes['PUT'][] = array(
         'Url' => $pUrl, 
         'CallBack' => $pCallBack
         );
   }

   public function Post($pUrl, $pCallBack) {
      $this->_Routes['POST'][] = array(
         'Url' => $pUrl, 
         'CallBack' => $pCallBack
         );
   }

   public function Delete($pUrl, $pCallBack) {
      $this->_Routes['DELETE'][] = array(
         'Url' => $pUrl, 
         'CallBack' => $pCallBack
         );
   }

   public function Run($pFile) {
      $lRouteData = $this->GetRouteData($this->Req2Array($pFile));
      //var_dump( $lRouteData );

      $DB = null;
      try
      {
         $DB = new Database();
      }
      catch (Exception $e)
      {
         echo $e->getMessage();
         die();
      }

      $lRouteData["CallBack"]($lRouteData["Param"], json_decode(file_get_contents("php://input"), true));
   }
}
$App = new App(array());

?>