<?php
/**
 * ArrBDD class file.
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @link http://www.doophp.com/arr-bdd
 * @copyright Copyright &copy; 2011 Leng Sheng Hong
 * @license http://www.doophp.com/license
 * @since 0.1
 */

/**
 * ArrBDD - a simple BDD library utilizing PHP associative arrays and closures
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @since 0.1
 */
 
class ArrBDD{

    protected $assertErrorKey;
    protected $assertError;
    protected $assertFailMsg = array();
    
    const SUBJECT_VAR_DUMP = 'var_dump';
    const SUBJECT_PRINT_R = 'print_r';
    const SUBJECT_VAR_EXPORT = 'var_export';
    
    public $subjectView;
    
    public function __construct($subjectView = ArrBDD::SUBJECT_VAR_DUMP){
        assert_options(ASSERT_CALLBACK, array(&$this, 'onAssertFail'));
        assert_options(ASSERT_WARNING, 0);     
        $this->subjectView = $subjectView;
    }
    
    protected function exportSubject($subject){
        switch($this->subjectView){
            case ArrBDD::SUBJECT_VAR_DUMP:
                var_dump($subject);
                break;
            case ArrBDD::SUBJECT_PRINT_R:
                print_r($subject);
                break;
            case ArrBDD::SUBJECT_VAR_EXPORT:
                var_export($subject, true);
                break;
        }
    }
    
    public function onAssertFail($file, $line, $expr) {        
        if( empty($expr) )
            $this->assertError = "Assertion failed in $file on line $line";
        else
            $this->assertError = "Assertion failed in $file on line $line: $expr";
        //print "<br/>[$this->assertErrorKey] \n\t$this->assertError\n<br/>";
        //print "<br/>$this->assertError<br/>";
        
        if( is_array($this->assertErrorKey) ){
            if( empty($this->assertFailMsg[$this->assertErrorKey[0]][$this->assertErrorKey[1]]) ){
                $this->assertFailMsg[$this->assertErrorKey[0]][$this->assertErrorKey[1]] = array();
            }
            $this->assertFailMsg[$this->assertErrorKey[0]][$this->assertErrorKey[1]][] = $this->assertError;        
        }
        else{
            if( empty($this->assertFailMsg[$this->assertErrorKey]) ){
                $this->assertFailMsg[$this->assertErrorKey] = array();
            }    
            $this->assertFailMsg[$this->assertErrorKey][] = $this->assertError;
        }
        $this->assertError = array( $this->assertError );    
    }
    
    public function run($specs, $includeSubject=false){        
        $testResults = array();
            
        foreach($specs as $specName => $spec){
            $results = array();            
            $subject = null;
            
            // Subject can be a closure or the actual value
            if( isset( $spec['subject'] ) ){
                $sbj = $spec['subject'];
                
                if(is_callable($sbj))
                    $subject = $sbj();
                else
                    $subject = $sbj;        
            }
            
            // include subject to the result
            if( $includeSubject ){
                ob_start();
                $this->exportSubject($subject);
                $content = ob_get_contents();
                ob_end_clean();
                $results['subject'] = $content;
            }
            
            foreach( $spec as $stepName => $step ){
                if($stepName=='subject') continue;
                
                $this->assertErrorKey = $stepName;
                
                // not a WHEN
                if( is_callable($step) ){
                    $rs = $step($subject);
                    // $results[$stepName] = ($rs) ? $rs : $this->assertError;
                    $this->evalAsserts($results, $rs, $stepName);                
                }
                // a WHEN 
                else if( is_array($step) ){
                    $_subject = null;
                    
                    // get inner step's subject, pass main step subject and inner subject to THEN closure
                    if( isset( $step['subject'] ) ){
                        $sbj = $step['subject'];                
                        
                        // Need passing back the main subject to the inner step subject closure
                        if(is_callable($sbj))
                            $_subject = $sbj($subject);
                        else
                            $_subject = $sbj;        
                    }        
                    
                    // include subject to the result
                    if( $includeSubject ){
                        ob_start();
                        $this->exportSubject($_subject);
                        $_content = ob_get_contents();
                        ob_end_clean();                    
                        $results[$stepName]['subject'] = $_content;
                    }                
                    
                    foreach( $step as $_stepName => $_step ){                
                        if($_stepName=='subject') continue;
                        
                        $this->assertErrorKey = array($stepName, $_stepName);                
                        $rs = $_step($_subject, $subject);
                        
                        $this->evalAsserts($results, $rs, $stepName, $_stepName);                    
                    }
                }
            }

            $testResults[$specName] = $results;
        }
        
        return $testResults;
    }

    protected function evalAsserts(&$results, &$rs, $stepName, $_stepName = NULL){
    
        // if an array is returned, check if the first value(assertion value) pass the test
        if( is_array($rs) ){
            //====== single assertion =====
            if( $rs[0]===true ){
                $rs = $rs[0];
            }
            // if fail, then use the assertion failed msg provided, if available
            else if( !empty($rs[1]) && is_string($rs[1]) ){
                $err = $rs[1];
                $this->assertError = array($err);
                $rs = false;        // failed
            }
            // Single assertion, if fail, and no debug msg specify, use default assertion error msg
            else if( empty($rs[1]) && empty($rs[0][1]) ){
                $rs = false;        
            }
            
            //====== multiple assertion =====
            else if( is_array($rs[0]) ){
                $asserts = $rs;
                
                // multiple assertion, get the error debug msg if available, eg. array( expr, "msg on the test" )
                // $assert[0] = expr,  $assert[1] = msg 
                $rs = array();
                foreach( $asserts as $assert ){
                    # var_dump($assert);
                    if( $assert[0]!==true ){
                        if( !empty($assert[1]) ){
                            $rs[] = $assert[1];                            
                        }
                        else{
                            $rs[] = false;//$this->assertError[0];
                        }
                    }
                    else{
                        $rs[] = true;
                    }
                }
            }
            
        }
        
        // assign result/fail msg for the steps (inner step, if inner is set)
        // if test passed, use boolean true, else if failed use the assertion error message
        if( isset($_stepName) ){
            $rst = &$results[$stepName][$_stepName];
            if(!empty($this->assertFailMsg[$stepName][$_stepName]))
                $afMsg = $this->assertFailMsg[$stepName][$_stepName];
        }
        else{
            $rst = &$results[$stepName];
            if(!empty($this->assertFailMsg[$stepName]))
                $afMsg = $this->assertFailMsg[$stepName];
        }    
        
        if( empty($afMsg) ){
            if($this->assertError===null) $this->assertError = false;
            $rst = ($rs) ? $rs : $this->assertError;  
        }
        else if(!empty($rs)){
            $this->assertError = $afMsg;
            $i = 0;
            foreach( $rs as &$rsvalue){
                if($rsvalue===true) continue;
                if($rsvalue===false)
                    $rsvalue = $this->assertError[$i];
                else
                    $rsvalue = $this->assertError[$i] . ': '. $rsvalue;                
                $i++;
            }
            $rst = $rs;                                        
        }
        else{
            if(is_array($this->assertError) && sizeof($this->assertError)===1){
                $rst = $this->assertError[0];                
                if(isset($afMsg[0]) && strpos($rst, 'Assertion failed in ')!==0){
                    $rst = $afMsg[0] . ': ' . $rst;
                }
            }
            else{              
                $rst = $this->assertError;
            }
        }    
        
        //errors will always be an array.
        if($rst!==true && !is_array($rst)){
            $rst = array($rst);
        }
        //var_dump($rst);
        $this->assertError = null;
    }


    //echo "\n============= Test Results ================\n";
    //print_r( arrBDD($specs) );
    //echo "\n\n\n\n\n\n";

    public function outputPre( $result ){
        echo '<pre>';
        print_r( $result );
    }

    public function outputVardump( $result ){
        echo '<pre>';
        var_dump( $result );
    }

    public function outputJSON( $result ){
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1970 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode( $result );
    }
}