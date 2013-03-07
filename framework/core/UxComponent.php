<?php

/**
 * 该文件包含了基于组件与事件驱动式编程的基础类
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * UxComponent是所有组件的基类，它采用属性与事件实现了所定义的协议。
 *
 * 属性由getter/setter方法来定义。如：
 * <pre>
 * $a = $component->text;     // 等同于 $a=$component->getText();
 * $component->text = 'abc';  // 等同于 $component->setText('abc');
 * </pre>
 * getter与setter方法的写法如下：
 * <pre>
 * // getter, 定义可读属性'text'
 * public function getText() { ... }
 * // setter, 定义可写属性'text'
 * public function setText($value) { ... }
 * </pre>
 *
 * 事件就是那些名称以'on'开头的方法，事件名就是方法名。 当事件被唤起的时候，附给该事件的函数将会被自动调用。
 *
 * 事件可以通过调用raiseEvent方法来唤起，它那些被附加的事件执行者将会根据他们附加的顺序自动调用。那些事件执行器必须有以下的标记，
 * <pre>
 * function eventHandler($event) { ... }
 * </pre>
 * where $event includes parameters associated with the event.
 *
 * To attach an event handler to an event, see {@link attachEventHandler}.
 * You can also use the following syntax:
 * <pre>
 * $component->onClick=$callback;    // or $component->onClick->add($callback);
 * </pre>
 * where $callback refers to a valid PHP callback. Below we show some callback examples:
 * <pre>
 * 'handleOnClick'                   // handleOnClick() is a global function
 * array($object,'handleOnClick')    // using $object->handleOnClick()
 * array('Page','handleOnClick')     // using Page::handleOnClick()
 * </pre>
 *
 * To raise an event, use {@link raiseEvent}. The on-method defining an event is
 * commonly written like the following:
 * <pre>
 * public function onClick($event)
 * {
 *     $this->raiseEvent('onClick',$event);
 * }
 * </pre>
 * where <code>$event</code> is an instance of {@link CEvent} or its child class.
 * One can then raise the event by calling the on-method instead of {@link raiseEvent} directly.
 *
 * 属性与事件均为不区分大小写。
 *
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: UxComponent.php 229 2012-11-23 03:13:46Z jimmy $
 * @package system.base
 * @since 1.0
 */
class UxComponent {

    private $_e;
    private $_m;

    /**
     * Returns a property value, an event handler list or a behavior based on its name.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using the following syntax to read a property or obtain event handlers:
     * <pre>
     * $value=$component->propertyName;
     * $handlers=$component->eventName;
     * </pre>
     * @param string $name the property name or event name
     * @return mixed the property value, event handlers attached to the event, or the named behavior
     * @throws CException if the property or event is not defined
     * @see __set
     */
    public function __get($name) {
        $getter = 'get' . $name;
        if (method_exists($this, $getter))
            return $this->$getter();
        else if (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name)) {
            // duplicating getEventHandlers() here for performance
            $name = strtolower($name);
            if (!isset($this->_e[$name]))
                $this->_e[$name] = new CList;
            return $this->_e[$name];
        }
        else if (isset($this->_m[$name]))
            return $this->_m[$name];
        else if (is_array($this->_m)) {
            foreach ($this->_m as $object) {
                if ($object->getEnabled() && (property_exists($object, $name) || $object->canGetProperty($name)))
                    return $object->$name;
            }
        }
        throw new UxException('Property "' . get_class($this) . '.' . $name . '" is not defined.');
    }

    /**
     * Sets value of a component property.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using the following syntax to set a property or attach an event handler
     * <pre>
     * $this->propertyName=$value;
     * $this->eventName=$callback;
     * </pre>
     * @param string $name the property name or the event name
     * @param mixed $value the property value or callback
     * @return mixed
     * @throws CException if the property/event is not defined or the property is read only.
     * @see __get
     */
    public function __set($name, $value) {
        $setter = 'set' . $name;
        if (method_exists($this, $setter))
            return $this->$setter($value);
        else if (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name)) {
            // duplicating getEventHandlers() here for performance
            $name = strtolower($name);
            if (!isset($this->_e[$name]))
                $this->_e[$name] = new CList;
            return $this->_e[$name]->add($value);
        }
        else if (is_array($this->_m)) {
            foreach ($this->_m as $object) {
                if ($object->getEnabled() && (property_exists($object, $name) || $object->canSetProperty($name)))
                    return $object->$name = $value;
            }
        }
        if (method_exists($this, 'get' . $name))
            throw new UxException('Property "' . get_class($this) . '.' . $name . '" is read only.');
        else
            throw new UxException('Property "' . get_class($this) . '.' . $name . '" is not defined.');
    }

    /**
     * Checks if a property value is null.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using isset() to detect if a component property is set or not.
     * @param string $name the property name or the event name
     * @return boolean
     */
    public function __isset($name) {
        $getter = 'get' . $name;
        if (method_exists($this, $getter))
            return $this->$getter() !== null;
        else if (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name)) {
            $name = strtolower($name);
            return isset($this->_e[$name]) && $this->_e[$name]->getCount();
        } else if (is_array($this->_m)) {
            if (isset($this->_m[$name]))
                return true;
            foreach ($this->_m as $object) {
                if ($object->getEnabled() && (property_exists($object, $name) || $object->canGetProperty($name)))
                    return $object->$name !== null;
            }
        }
        return false;
    }

    /**
     * Sets a component property to be null.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using unset() to set a component property to be null.
     * @param string $name the property name or the event name
     * @throws CException if the property is read only.
     * @return mixed
     */
    public function __unset($name) {
        $setter = 'set' . $name;
        if (method_exists($this, $setter))
            $this->$setter(null);
        else if (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name))
            unset($this->_e[strtolower($name)]);
        else if (is_array($this->_m)) {
            if (isset($this->_m[$name]))
                $this->detachBehavior($name);
            else {
                foreach ($this->_m as $object) {
                    if ($object->getEnabled()) {
                        if (property_exists($object, $name))
                            return $object->$name = null;
                        else if ($object->canSetProperty($name))
                            return $object->$setter(null);
                    }
                }
            }
        }
        else if (method_exists($this, 'get' . $name))
            throw new UxException('Property "' . get_class($this) . '.' . $name . '" is read only.');
    }

    /**
     * Calls the named method which is not a class method.
     * Do not call this method. This is a PHP magic method that we override
     * to implement the behavior feature.
     * @param string $name the method name
     * @param array $parameters method parameters
     * @return mixed the method return value
     */
    public function __call($name, $parameters) {
        if ($this->_m !== null) {
            foreach ($this->_m as $object) {
                if ($object->getEnabled() && method_exists($object, $name))
                    return call_user_func_array(array($object, $name), $parameters);
            }
        }
        if (class_exists('Closure', false) && $this->canGetProperty($name) && $this->$name instanceof Closure)
            return call_user_func_array($this->$name, $parameters);
        throw new UxException('"' . get_class($this) . '" and its behaviors do not have a method or closure named "' . $name . '".');
    }

    /**
     * Returns the named behavior object.
     * The name 'asa' stands for 'as a'.
     * @param string $behavior the behavior name
     * @return IBehavior the behavior object, or null if the behavior does not exist
     */
    public function asa($behavior) {
        return isset($this->_m[$behavior]) ? $this->_m[$behavior] : null;
    }

    /**
     * Determines whether a property is defined.
     * A property is defined if there is a getter or setter method
     * defined in the class. Note, property names are case-insensitive.
     * @param string $name the property name
     * @return boolean whether the property is defined
     * @see canGetProperty
     * @see canSetProperty
     */
    public function hasProperty($name) {
        return method_exists($this, 'get' . $name) || method_exists($this, 'set' . $name);
    }

    /**
     * Determines whether a property can be read.
     * A property can be read if the class has a getter method
     * for the property name. Note, property name is case-insensitive.
     * @param string $name the property name
     * @return boolean whether the property can be read
     * @see canSetProperty
     */
    public function canGetProperty($name) {
        return method_exists($this, 'get' . $name);
    }

    /**
     * Determines whether a property can be set.
     * A property can be written if the class has a setter method
     * for the property name. Note, property name is case-insensitive.
     * @param string $name the property name
     * @return boolean whether the property can be written
     * @see canGetProperty
     */
    public function canSetProperty($name) {
        return method_exists($this, 'set' . $name);
    }

    /**
     * Determines whether an event is defined.
     * An event is defined if the class has a method named like 'onXXX'.
     * Note, event name is case-insensitive.
     * @param string $name the event name
     * @return boolean whether an event is defined
     */
    public function hasEvent($name) {
        return !strncasecmp($name, 'on', 2) && method_exists($this, $name);
    }

    /**
     * Checks whether the named event has attached handlers.
     * @param string $name the event name
     * @return boolean whether an event has been attached one or several handlers
     */
    public function hasEventHandler($name) {
        $name = strtolower($name);
        return isset($this->_e[$name]) && $this->_e[$name]->getCount() > 0;
    }

    /**
     * Returns the list of attached event handlers for an event.
     * @param string $name the event name
     * @return CList list of attached event handlers for the event
     * @throws CException if the event is not defined
     */
    public function getEventHandlers($name) {
        if ($this->hasEvent($name)) {
            $name = strtolower($name);
            if (!isset($this->_e[$name]))
                $this->_e[$name] = new CList;
            return $this->_e[$name];
        }
        else
            throw new UxException('Event "' . get_class($this) . '.' . $name . '" not defined.');
    }

    /**
     * Attaches an event handler to an event.
     *
     * An event handler must be a valid PHP callback, i.e., a string referring to
     * a global function name, or an array containing two elements with
     * the first element being an object and the second element a method name
     * of the object.
     *
     * An event handler must be defined with the following signature,
     * <pre>
     * function handlerName($event) {}
     * </pre>
     * where $event includes parameters associated with the event.
     *
     * This is a convenient method of attaching a handler to an event.
     * It is equivalent to the following code:
     * <pre>
     * $component->getEventHandlers($eventName)->add($eventHandler);
     * </pre>
     *
     * Using {@link getEventHandlers}, one can also specify the excution order
     * of multiple handlers attaching to the same event. For example:
     * <pre>
     * $component->getEventHandlers($eventName)->insertAt(0,$eventHandler);
     * </pre>
     * makes the handler to be invoked first.
     *
     * @param string $name the event name
     * @param callback $handler the event handler
     * @throws CException if the event is not defined
     * @see detachEventHandler
     */
    public function attachEventHandler($name, $handler) {
        $this->getEventHandlers($name)->add($handler);
    }

    /**
     * Detaches an existing event handler.
     * This method is the opposite of {@link attachEventHandler}.
     * @param string $name event name
     * @param callback $handler the event handler to be removed
     * @return boolean if the detachment process is successful
     * @see attachEventHandler
     */
    public function detachEventHandler($name, $handler) {
        if ($this->hasEventHandler($name))
            return $this->getEventHandlers($name)->remove($handler) !== false;
        else
            return false;
    }

    /**
     * Raises an event.
     * This method represents the happening of an event. It invokes
     * all attached handlers for the event.
     * @param string $name the event name
     * @param CEvent $event the event parameter
     * @throws CException if the event is undefined or an event handler is invalid.
     */
    public function raiseEvent($name, $event) {
        $name = strtolower($name);
        if (isset($this->_e[$name])) {
            foreach ($this->_e[$name] as $handler) {
                if (is_string($handler))
                    call_user_func($handler, $event);
                else if (is_callable($handler, true)) {
                    if (is_array($handler)) {
                        // an array: 0 - object, 1 - method name
                        list($object, $method) = $handler;
                        if (is_string($object)) // static method call
                            call_user_func($handler, $event);
                        else if (method_exists($object, $method))
                            $object->$method($event);
                        else
                            throw new UxException('Event "' . get_class($this) . '.' . $name . '" is attached with an invalid handler "{' . $handler[1] . '.');
                    }
                    else // PHP 5.3: anonymous function
                        call_user_func($handler, $event);
                }
                else
                    throw new UxException('Event "' . get_class($this) . '.' . $name . '" is attached with an invalid handler "{' . $handler[1] . '.');
                // stop further handling if param.handled is set true
                if (($event instanceof CEvent) && $event->handled)
                    return;
            }
        }
        else if (YII_DEBUG && !$this->hasEvent($name))
            throw new UxException('Event "' . get_class($this) . '.' . $name . '" not defined.');
    }

    /**
     * Evaluates a PHP expression or callback under the context of this component.
     *
     * Valid PHP callback can be class method name in the form of
     * array(ClassName/Object, MethodName), or anonymous function (only available in PHP 5.3.0 or above).
     *
     * If a PHP callback is used, the corresponding function/method signature should be
     * <pre>
     * function foo($param1, $param2, ..., $component) { ... }
     * </pre>
     * where the array elements in the second parameter to this method will be passed
     * to the callback as $param1, $param2, ...; and the last parameter will be the component itself.
     *
     * If a PHP expression is used, the second parameter will be "extracted" into PHP variables
     * that can be directly accessed in the expression. See {@link http://us.php.net/manual/en/function.extract.php PHP extract}
     * for more details. In the expression, the component object can be accessed using $this.
     *
     * @param mixed $_expression_ a PHP expression or PHP callback to be evaluated.
     * @param array $_data_ additional parameters to be passed to the above expression/callback.
     * @return mixed the expression result
     * @since 1.1.0
     */
    public function evaluateExpression($_expression_, $_data_ = array()) {
        if (is_string($_expression_) && !function_exists($_expression_)) {
            extract($_data_);
            return eval('return ' . $_expression_ . ';');
        } else {
            $_data_[] = $this;
            return call_user_func_array($_expression_, $_data_);
        }
    }

}

/**
 * CEvent is the base class for all event classes.
 *
 * It encapsulates the parameters associated with an event.
 * The {@link sender} property describes who raises the event.
 * And the {@link handled} property indicates if the event is handled.
 * If an event handler sets {@link handled} to true, those handlers
 * that are not invoked yet will not be invoked anymore.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: UxComponent.php 229 2012-11-23 03:13:46Z jimmy $
 * @package system.base
 * @since 1.0
 */
class CEvent extends UxComponent {

    /**
     * @var object the sender of this event
     */
    public $sender;

    /**
     * @var boolean whether the event is handled. Defaults to false.
     * When a handler sets this true, the rest of the uninvoked event handlers will not be invoked anymore.
     */
    public $handled = false;

    /**
     * @var mixed additional event parameters.
     * @since 1.1.7
     */
    public $params;

    /**
     * Constructor.
     * @param mixed $sender sender of the event
     * @param mixed $params additional parameters for the event
     */
    public function __construct($sender = null, $params = null) {
        $this->sender = $sender;
        $this->params = $params;
    }

}

/**
 * CEnumerable is the base class for all enumerable types.
 *
 * To define an enumerable type, extend CEnumberable and define string constants.
 * Each constant represents an enumerable value.
 * The constant name must be the same as the constant value.
 * For example,
 * <pre>
 * class TextAlign extends CEnumerable
 * {
 *     const Left='Left';
 *     const Right='Right';
 * }
 * </pre>
 * Then, one can use the enumerable values such as TextAlign::Left and
 * TextAlign::Right.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: UxComponent.php 229 2012-11-23 03:13:46Z jimmy $
 * @package system.base
 * @since 1.0
 */
class CEnumerable {
    
}