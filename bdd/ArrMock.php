<?php
/**
 * ArrMock class file.
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @link http://www.doophp.com/arr-mock
 * @copyright Copyright &copy; 2011 Leng Sheng Hong
 * @license http://www.doophp.com/license
 * @since 0.1
 */

/**
 * ArrMock - a simple tool for mocking objects
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @since 0.1
 */
class ArrMock {
    public $methods = array();    
    protected $toStringVal;
    protected $lastMethod;
    protected $lastStaticMethod;
    protected $lastArgs;
    
    /**
     * Prevent direct object creation of ArrMock
     */
    final private function  __construct(){}

    /**
     * Prevent object cloning of ArrMock
     */
    final private function  __clone(){}
    
    /**
     * This must be the first call to create the Mock Object
     */
    public static function create( $className = 'AnonClass', $staticAttr = null ){
        if(empty($className) || !is_string($className)){
            throw new Exception('Class name of the mock object is required');
        }
        
        $className .= 'Mock';
        
        $staticAttrStr = '';
        if(is_array($staticAttr)){
            foreach( $staticAttr as $attr ){
                $staticAttrStr .= 'public static $' . $attr .';';
            }
        }
        
        eval(<<<EOF
class $className extends ArrMock{
    public static \$staticMethods = array();
    protected \$selfClassName = '$className';
    $staticAttrStr
    
    public function staticAttr(\$attr, \$val){
        self::\$\$attr = \$val;
    }
    
    public function returns( \$returnVal = null, \$position = null, \$execFunc = null ){
        \$return = parent::returns( \$returnVal, \$position, \$execFunc );
        
        //if the return is \$this, meaning it's a non static method, return \$this straight away
        if( isset(\$this->lastMethod) && is_object(\$return) && is_subclass_of(\$return, 'ArrMock') ){
            return \$this;        
        }
        
        if(empty(self::\$staticMethods[\$this->lastMethod][\$this->lastArgs])){
            self::\$staticMethods[\$this->lastMethod][\$this->lastArgs] = array();
            self::\$staticMethods[\$this->lastMethod][\$this->lastArgs]['returns'] = array();
        }
        
        \$ret = &self::\$staticMethods[\$this->lastStaticMethod][\$this->lastArgs]['returns'];
        self::getReturnVal(\$ret, \$returnVal, \$position);

        return \$this;
    }
    
    public function handle(\$func){            
        \$return = parent::handle( \$func );
        
        //if the return is \$this, meaning it's a non static method, return \$this straight away
        if( isset(\$this->lastMethod) && is_object(\$return) && is_subclass_of(\$return, 'ArrMock') ){
            return \$this;        
        }

        self::\$staticMethods[\$this->lastStaticMethod]['handler'] = \$func;

        return \$this;    
    }
    
    public function totalCalls( \$method ){
        // get total method calls on both static and non-static coz they can't have the same name
        \$args = array_slice(func_get_args(), 1); 
        \$total = parent::calcTotalCalls(\$this->methods, \$method, \$args);
        
        if( \$total===null && !empty(self::\$staticMethods[\$method]) ){        
            \$total = parent::calcTotalCalls(self::\$staticMethods, \$method, \$args);
        }        
        return (int)\$total;
    }
    
    public static function __callStatic(\$name, \$arguments) {
        if( isset( self::\$staticMethods[\$name] ) ){
            \$args = var_export(\$arguments, true);
            if( isset( self::\$staticMethods[\$name][\$args] ) ){
                \$ret = &self::\$staticMethods[\$name][\$args];
                if( !isset(\$ret['count']) )
                    \$ret['count'] = 0;
                    
                \$count = \$ret['count']++;
                \$retLength = sizeof(\$ret['returns']);
                
                if( \$count >= \$retLength )
                    \$count = \$retLength - 1;
                    
                return \$ret['returns'][ \$count ];
            }                
            else if( !empty(self::\$staticMethods[\$name]['handler']) ){
            
                if( !isset(self::\$staticMethods[\$name]['handlerCallCount']) )
                    self::\$staticMethods[\$name]['handlerCallCount'] = 0;
                self::\$staticMethods[\$name]['handlerCallCount']++;
                
                \$handler = self::\$staticMethods[\$name]['handler'];
                return \$handler(\$arguments);
            }
        }
        throw new Exception("Call to undefined static method ". __CLASS__ ."::\$name()");
    }
}
EOF
        );
        return new $className;
    }
    
    public function toString( $stringVal ){
        $this->toStringVal = $stringVal;
        return $this;
    }
    
    public function method( $name ){
        $this->lastMethod = $name;
        $this->lastStaticMethod = $this->lastArgs = null;   //reset
        return $this;
    }
    
    public function staticMethod( $name ){
        $this->lastStaticMethod = $name;
        $this->lastMethod = $this->lastArgs = null;   //reset   
        return $this;
    }
    
    public function args(){
        if( isset($this->lastMethod) || isset($this->lastStaticMethod) ){
            $args = func_get_args();
            // args is a null or no args at all
            $this->lastArgs = var_export($args, true);
        }
        return $this;
    }
    
    public function handle($func){            
        if(isset($this->lastMethod)){            
            $this->methods[$this->lastMethod]['handler'] = $func;
        }
        else if(isset($this->lastStaticMethod)){  
            return $func;
        }
        return $this;    
    }
    
    public function returns( $returnVal = null, $position = null, $execFunc = null){
        if( isset($this->lastMethod) || isset($this->lastStaticMethod) ){    
            if( $this->lastArgs===null ){
                $this->lastArgs = var_export(array(), true);
            }
            
            if(isset($this->lastMethod)){
            
                if(empty($this->methods[$this->lastMethod][$this->lastArgs])){
                    $this->methods[$this->lastMethod][$this->lastArgs] = array();
                    $this->methods[$this->lastMethod][$this->lastArgs]['returns'] = array();
                }
                
                $ret = &$this->methods[$this->lastMethod][$this->lastArgs]['returns'];
                self::getReturnVal($ret, $returnVal, $position);
            }
            else{
                return $returnVal;
            }
        }
        
        return $this;
    }
    
    protected static function getReturnVal(&$ret, $returnVal, $position){                
        if( is_int($position) ){
            $retLength = sizeof($ret);
            
            if( $retLength==0 ){
                $ret[] = $returnVal;
                $retLength++;
            }
            
            $prevVal = $ret[ $retLength-1 ];
            $position = $position - 1 - $retLength;
            
            if( $position > 0 ){
                $fill = array_fill($retLength, $position, $prevVal);
                $ret = array_merge($ret, $fill);
                //echo "$retLength, $position, $prevVal";
                //var_dump( $fill );
            }
            $ret[] = $returnVal;
        }
        else{
            $ret[] = $returnVal;
        }    
    }
        
    public function __toString() {
        if(isset($this->toStringVal))
            return $this->toStringVal;
    }

    public function __call($name, $arguments) {
        if( isset( $this->methods[$name] ) ){
            $args = var_export($arguments, true);
            if( isset( $this->methods[$name][$args] ) ){
                $ret = &$this->methods[$name][$args];
                
                if( !isset($ret['count']) )
                    $ret['count'] = 0;

                $count = $ret['count']++;
                $retLength = sizeof($ret['returns']);
                
                if( $count >= $retLength )
                    $count = $retLength - 1;
                    
                return $ret['returns'][ $count ];
            }
            else if( !empty($this->methods[$name]['handler']) ){
                if( !isset($this->methods[$name]['handlerCallCount']) )
                    $this->methods[$name]['handlerCallCount'] = 0;
                $this->methods[$name]['handlerCallCount']++;
                return $this->methods[$name]['handler']($arguments);
            }
        }
        
        throw new Exception("Call to undefined method ". $this->selfClassName ."::$name()");
    }
    
    protected function calcTotalCalls( $methodsList, $method, $args ){
        // get total method calls on both static and non-static coz they can't have the same name
        if( !empty($methodsList[$method]) ){
            $methodsCalled = $methodsList[$method];
            $total = 0;
            
            $emptyArgs = (sizeof($args)===0);
            //null should be converted to an empty array to be var_export as key
            if( sizeof($args)===1 && $args[0]===null ) 
                $args = array();
            
            if(!$emptyArgs)
                $args = var_export($args, true);
                        
            // add up all methods call count
            foreach($methodsCalled as $argsKey => $mc){
                if($argsKey==='handlerCallCount') continue;
                
                if($argsKey==='handler'){
                    if($emptyArgs && isset($methodsCalled['handlerCallCount']))
                        $total += $methodsCalled['handlerCallCount'];
                }
                else if(isset($mc['count'])){
                    // all methods, ignore matching args
                    if( $emptyArgs ){
                        $total += $mc['count'];
                    }
                    // try matching the arguments as key
                    else if( $argsKey===$args ){
                        $total += $mc['count']; 
                    }
                }
            }
            return $total;
        }
    }
}

