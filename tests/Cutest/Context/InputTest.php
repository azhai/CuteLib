<?php
namespace Cutest\Context;
use \PHPUnit_Framework_TestCase as TestCase;
use \Cute\Context\Input;
use \ArrayIterator;
use \MultipleIterator;

class InputMock extends Input
{
    protected $raw_data = array();  // 原始数据
    
    protected function filterData($key, $type)
    {
        $type = self::detectType($type);
        if (array_key_exists($key, $this->raw_data)) {
            return filter_var($this->raw_data[$key], $type);
        } else {
            return filter_input($this->input_type, $key, $type);
        }
    }
    
    protected function filterArrayData($types)
    {
        if (is_array($types)) {
            foreach ($types as $key => & $type) {
                if (is_array($type)) {
                    $type['filter'] = self::detectType($type['filter']);
                } else {
                    $type = self::detectType($type);
                }
            }
        } else {
            $types = self::detectType($types);
        }
        if (! empty($this->raw_data)) {
            return filter_var_array($this->raw_data, $types);
        } else {
            return filter_input_array($this->input_type, $types);
        }
    }
    
    public function setArrayData(MultipleIterator& $iterator)
    {
        foreach ($iterator as $pair) {
            list($key, $value) = $pair;
            $key = rtrim($key, '[]');
            if (! array_key_exists($key, $this->raw_data)) {
                $this->raw_data[$key] = $value;
            } else if (! is_array($this->raw_data[$key])) {
                $this->raw_data[$key] = array($this->raw_data[$key], $value);
            } else {
                array_push($this->raw_data[$key], $value);
            }
        }
    }
}


class InputTest extends TestCase
{
    protected $get = array();
    protected $post = array();
    
    public function setUp()
    {
        $keys = array('ga', 'gb', 'gc[]', 'gc[]');
        $values = array(13, 31, 'g', 33);
        $this->get = new MultipleIterator();
        $this->get->attachIterator(new ArrayIterator($keys), 'key');
        $this->get->attachIterator(new ArrayIterator($values), 'value');
        $keys = array('pa', 'pb[]', 'pb[]');
        $values = array(7, 71, 72);
        $this->post = new MultipleIterator();
        $this->post->attachIterator(new ArrayIterator($keys), 'key');
        $this->post->attachIterator(new ArrayIterator($values), 'value');
    }

    public function test01Get()
    {
        $input = InputMock::getInstance('GET');
        $input->setArrayData($this->get);
        $this->assertEquals('13', $input->get('ga'));
        $this->assertEquals(31, $input->get('gb', 0, 'int'));
        $this->assertEquals(array(false, 33), $input->get('gc', array(), 'int'));
    }

    public function test02Post()
    {
        $input = InputMock::getInstance('POST');
        $input->setArrayData($this->post);
        $this->assertEquals('7', $input->get('pa'));
        $this->assertEquals(array(71, 72), $input->get('pb', array(), 'int'));
    }

    public function test03Request()
    {
        $input = InputMock::getInstance('POST');
        $input->setArrayData($this->get);
        $input->setArrayData($this->post);
        $this->assertEquals(31, $input->request('gb', 0, 'int'));
        $this->assertEquals('7', $input->request('pa'));
    }
}
