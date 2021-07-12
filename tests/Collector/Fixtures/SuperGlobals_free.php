<?php

namespace Tests\SensioLabs\Deptrac\Collector\Fixture;

class SuperGlobals_free {
    public function aMethod() {
        $a = $get['abc'];
        $b = $POST;
    }
}