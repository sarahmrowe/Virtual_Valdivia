<?php
// Desc: utility/tool shared functions
class C_Utility{
    
    // Desc: Utility function to add slashes - add slashes only the magic_quotes_gpc is set to off
    // It is strongly recommended that to turn off the magic quotes in the configuration setting
    public static function add_slashes($str){
        if (get_magic_quotes_gpc() == 1) {
            return ($str);
        }else{ 
            return (addslashes($str));
        }
    }
    
     // Indents JSON string to be more readable
     public static function indent_json($json) {     
        $result    = '';
        $pos       = 0;
        $strLen    = strlen($json);
        $indentStr = '  ';
        $newLine   = "\n";
     
        for($i = 0; $i <= $strLen; $i++) {
            
            // Grab the next character in the string
            $char = substr($json, $i, 1);
            
            // If this character is the end of an element, 
            // output a new line and indent the next line
            if($char == '}' || $char == ']') {
                $result .= $newLine;
                $pos --;
                for ($j=0; $j<$pos; $j++) {
                    $result .= $indentStr;
                }
            }
            
            // Add the character to the result string
            $result .= $char;
     
            // If the last character was the beginning of an element, 
            // output a new line and indent the next line
            #bug: it adds newline wronly when the value is comma(,). Removed for now
            //if ($char == ',' || $char == '{' || $char == '[') {
            if ($char == '{' || $char == '[') {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos ++;
                }
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }
        }
     
        return $result;
    }
            
    // convert boolean to literal string used by jqgrid script;
    public static function literalBool($boolValue){
        return ($boolValue)?'true':'false';
    }

}

?>