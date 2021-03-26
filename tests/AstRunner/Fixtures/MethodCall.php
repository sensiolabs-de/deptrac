<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\AstRunner\Fixtures;

class DummyClassA
{
    public function returnViolationClass() : DummyViolationClass
    {
        return new DummyViolationClass();
    }
}

// DummyClassC.php
class DummyClassC
{
    public function doSomething()
    {
        $classA = new DummyClassA();
        $violationClass = $classA->returnViolationClass();
        $violationClass->foo();
    }
}

// DummyViolationClass.php
class DummyViolationClass
{
    public function foo() {

    }
}
