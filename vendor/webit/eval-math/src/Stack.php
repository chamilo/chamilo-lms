<?php
namespace Webit\Util\EvalMath;

/**
 * Class Stack
 */
class Stack
{
    /**
     * @var array
     */
    public $stack = array();

    /**
     * @var int
     */
    public $count = 0;
    
    public function push($val)
    {
        $this->stack[$this->count] = $val;
        $this->count++;
    }
    
    public function pop()
    {
        if ($this->count > 0) {
            $this->count--;
            return $this->stack[$this->count];
        }

        return null;
    }
    
    public function last($n=1)
    {
        $key = $this->count - $n;
    		
        return array_key_exists($key,$this->stack) ? $this->stack[$key] : null;
    }
}
