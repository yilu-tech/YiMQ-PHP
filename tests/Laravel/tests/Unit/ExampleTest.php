<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_example()
    {

        $runtime = new \parallel\Runtime();

        $future = $runtime->run(function(){
            for ($i = 0; $i < 500; $i++)
                echo "*";

            return "easy";
        });

        for ($i = 0; $i < 500; $i++) {
            echo ".";
        }

        printf("\nUsing \\parallel\\Runtime is %s\n", $future->value());


        $this->assertTrue(true);
    }
}
