<?php

namespace gh_rboliveira\football_data\tests;

final class TestingUtils
{

    public static function getPrivateMethod($obj, $name)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }
}