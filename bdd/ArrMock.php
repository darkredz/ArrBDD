<?php
/**
 * ArrMock class file.
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @link http://www.doophp.com/arr-mock
 * @copyright Copyright &copy; 2011 Leng Sheng Hong
 * @license http://www.doophp.com/license
 * @since 1.0
 */

/**
 * ArrMock - a simple tool for mocking objects
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @since 1.0
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
    public static function create( $className = 'AnonClass', $suffix = 'Mock' ){
        if(empty($className) || !is_string($className)){
            throw new Exception('Class name of the mock object is required');
        }
        
        $className .= 'Mock';
        
        eval(<<<EOF
class $className extends ArrMock{
    public static \$staticMethods = array();
    protected \$selfClassName = '$className';

    public function returns( \$returnVal ){
        \$return = parent::returns( \$returnVal );
        
        //if the return is \$this, meaning it's a non static method, return \$this straight away
        if( isset(\$this->lastMethod) && is_object(\$return) && is_subclass_of(\$return, 'ArrMock') ){
            \$this->lastMethod = \$this->lastStaticMethod = \$this->lastArgs = null;        
            return \$this;        
        }
        
        self::\$staticMethods[\$this->lastStaticMethod][\$this->lastArgs] = \$returnVal;
        
        //reset
        \$this->lastMethod = \$this->lastStaticMethod = \$this->lastArgs = null;            
        return \$this;
    }

    public static function __callStatic(\$name, \$args) {
        if( isset( self::\$staticMethods[\$name] ) ){
            \$args = var_export(\$args, true);
            if( isset( self::\$staticMethods[\$name][\$args] ) )
                return self::\$staticMethods[\$name][\$args];
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
        $this->lastStaticMethod = null;
        return $this;
    }
    
    public function staticMethod( $name ){
        $this->lastStaticMethod = $name;
        $this->lastMethod = null;
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
    
    public function returns( $returnVal ){
        if( isset($this->lastMethod) || isset($this->lastStaticMethod) ){    
            if( $this->lastArgs===null ){
                $this->lastArgs = var_export(array(), true);
            }
            
            if(isset($this->lastMethod))
                $this->methods[$this->lastMethod][$this->lastArgs] = $returnVal;
            else{
                //self::$staticMethods[$this->lastStaticMethod][$this->lastArgs] = $returnVal;
                return $returnVal;
            }
        }
        
        //$this->lastMethod = $this->lastStaticMethod = $this->lastArgs = null;
        return $this;
    }
        
    public function __toString() {
        if(isset($this->toStringVal))
            return $this->toStringVal;
    }

    public function __call($name, $args) {
        if( isset( $this->methods[$name] ) ){
            $args = var_export($args, true);
            if( isset( $this->methods[$name][$args] ) )
                return $this->methods[$name][$args];
        }
        
        throw new Exception("Call to undefined method ". $this->selfClassName ."::$name()");
    }
}

