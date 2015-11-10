<?php
/**
 * A Dependency Locator for managing injection and configuration of resources
 *
 * User: Chris Thomas
 * Date: 10/11/2015
 * Time: 12:03
 *
 * notes:
 *  -   This is my first attempt at a dependency container
 *  -   I'm trying to write this without referencing another container, to explore whether I fully understand the idea
 *  -   I haven't figured out how I'm going to allow targets to require dependencies yet
 */
class Amslib_Container
{
    protected $list;

    protected function process($object, $dependencies = [])
    {
        if($object instanceof Closure){
            return $object($this, $dependencies);
        }else if(is_callable($object)){
            return call_user_func_array($object,[$this,$dependencies]);
        }else if(is_string($object) && class_exists($object) && class_exists("ReflectionClass")){
            $rc = new ReflectionClass($object);
            return $rc->newInstanceArgs([$this,$dependencies]);
        }else if(is_string($object) && !empty($this[$object])){
            return $this[$object];
        }else{
            throw new \InvalidArgumentException("\$object parameter was not recognised type [closure, callable, string]");
        }
    }

    public function __construct()
    {
        $this->list = [];
    }

    public function create($name, $target, array $dependencies = [])
    {
        if(!is_string($name) || empty($name)){
            throw new InvalidArgumentException("the \$name parameter was not valid [name = '".Amslib_Debug::vdump($name)."']");
        }

        $this[$name] = $this->process($target, $dependencies);
    }

    public function factory($name, $target, array $dependencies = [])
    {
        $closure = function() use ($target, $dependencies) {
            return $this->process($target, $dependencies);
        };

        //  create a factory method which creates on demand a new dependency
        $this[$name] = $closure->bindTo($this);
    }

    public function remove($name)
    {
        unset($this[$name]);
    }

    public function __set($name,$value)
    {
        //  set one of the dependencies
        $this->list[$name] = $value;
    }

    public function __get($name)
    {
        if(!is_string($name) || empty($name)){
            throw new InvalidArgumentException("the \$name parameter was not valid [name = '".Amslib_Debug::vdump($name)."']");
        }

        $target = $this->list[$name];

        return $target instanceof Closure ? $target() : $target;
    }

    public function __unset($name)
    {
        unset($this->list[$name]);
    }

    public function __isset($name)
    {
        if(!array_key_exists($name,$this->list)){
            throw new InvalidArgumentException("the \$name parameter does not exist in container [name = '".Admin_Debug::vdump($name)."']");
        }
    }
}