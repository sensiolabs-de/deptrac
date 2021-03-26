<?php 

namespace Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance;

use Tests\Qossmic\Deptrac\AstRunner\Fixtures\BasicInheritance\FixtureBasicInheritanceWithNoiseFoo1 as foo1;

class FixtureBasicInheritanceWithNoiseFoo1 {}
class FixtureBasicInheritanceWithNoiseFoo2 {}

class FixtureBasicInheritanceWithNoiseA {

    function a(foo1 $a) {
        new foo1();
        new FixtureBasicInheritanceWithNoiseFoo1();
    }

}
class FixtureBasicInheritanceWithNoiseB extends FixtureBasicInheritanceWithNoiseA { }
class FixtureBasicInheritanceWithNoiseC extends FixtureBasicInheritanceWithNoiseB { }

new FixtureBasicInheritanceWithNoiseFoo2();
