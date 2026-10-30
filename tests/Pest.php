<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests run against the booted Laravel application. Unit tests stay
| plain PHPUnit test cases so they remain fast and framework-independent.
|
*/

pest()->extend(TestCase::class)->in('Feature');
