<?php

namespace Tests\SensioLabs\Deptrac\Collector\Fixture;

class SuperGlobals_contained {
    public function aMethod() {
        if (isset($_GET['test'])) {

        }
    }
}