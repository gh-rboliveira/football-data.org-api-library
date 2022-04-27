<?php

namespace gh_rboliveira\football_data\tests;

use PHPUnit\Framework\TestCase;
use gh_rboliveira\football_data\FootballData;
use gh_rboliveira\football_data\tests\TestingUtils;

final class UnitTests extends TestCase
{

    private $fd;

    protected function setUp(): void
    {
        $this->fd = new FootballData("AUTH_TOKEN");
    }

    /** @test */
    public function test_constructor_without_key()
    {
        $this->expectException(\InvalidArgumentException::class);
        $fd = new FootballData("");

    }

    /** @test */
    public function test_append_query_empty_value()
    {
        $method = TestingUtils::getPrivateMethod($this->fd, 'append_query');
        $returned = $method->invoke($this->fd, "competitions/?", "plan", "");
        $this->assertEquals("competitions/?", $returned);
    }

    /** @test */
    public function test_append_query_with_value()
    {
        $method = TestingUtils::getPrivateMethod($this->fd, 'append_query');
        $returned = $method->invoke($this->fd, "competitions/?", "plan", "TIER_ONE");
        $this->assertEquals("competitions/?plan=TIER_ONE", $returned);
    }

    /** @test */
    public function test_validate_string_ints_with_error()
    {
        $method = TestingUtils::getPrivateMethod($this->fd, 'validate_string_ints');
        $returned = $method->invoke($this->fd, "1,2,a");
        $this->assertEquals(false, $returned);
    }

    /** @test */
    public function test_validate_string_ints_without_error()
    {
        $method = TestingUtils::getPrivateMethod($this->fd, 'validate_string_ints');
        $returned = $method->invoke($this->fd, "1,2,4");
        $this->assertEquals(true, $returned);
    }

    /** @test */
    public function test_validate_plan_with_error()
    {
        $method = TestingUtils::getPrivateMethod($this->fd, 'validate_plan');
        $returned = $method->invoke($this->fd, "WRONG_PLAN");
        $this->assertEquals(false, $returned);
    }

    /** @test */
    public function test_validate_plan_without_error()
    {
        $method = TestingUtils::getPrivateMethod($this->fd, 'validate_plan');
        $returned = $method->invoke($this->fd, "TIER_ONE");
        $this->assertEquals(true, $returned);
    }

    /** @test */
    public function test_validate_season_with_error()
    {
        $method = TestingUtils::getPrivateMethod($this->fd, 'validate_season');
        $returned = $method->invoke($this->fd, "1");
        $this->assertEquals(false, $returned);
    }

    /** @test */
    public function test_validate_season_without_error()
    {
        $method = TestingUtils::getPrivateMethod($this->fd, 'validate_season');
        $returned = $method->invoke($this->fd, "2021");
        $this->assertEquals(true, $returned);
    }

    /** @test */
    public function test_validate_standing_type_with_error()
    {
        $method = TestingUtils::getPrivateMethod($this->fd, 'validate_standing_type');
        $returned = $method->invoke($this->fd, "WRONG_STANDING_TYPE");
        $this->assertEquals(false, $returned);
    }

    /** @test */
    public function test_validate_standing_type_without_error()
    {
        $method = TestingUtils::getPrivateMethod($this->fd, 'validate_standing_type');
        $returned = $method->invoke($this->fd, "HOME");
        $this->assertEquals(true, $returned);
    }

    /** @test */
    public function test_validate_date_with_error()
    {
        $method = TestingUtils::getPrivateMethod($this->fd, 'validate_date');
        $returned = $method->invoke($this->fd, "1-01.1");
        $this->assertEquals(false, $returned);
    }

    /** @test */
    public function test_validate_date_without_error()
    {
        $method = TestingUtils::getPrivateMethod($this->fd, 'validate_date');
        $returned = $method->invoke($this->fd, "2022-01-01");
        $this->assertEquals(true, $returned);
    }

    /** @test */
    public function test_validate_status_with_error()
    {
        $method = TestingUtils::getPrivateMethod($this->fd, 'validate_status');
        $returned = $method->invoke($this->fd, "WRONG_STATUS");
        $this->assertEquals(false, $returned);
    }

    /** @test */
    public function test_validate_status_without_error()
    {
        $method = TestingUtils::getPrivateMethod($this->fd, 'validate_status');
        $returned = $method->invoke($this->fd, "LIVE");
        $this->assertEquals(true, $returned);
    }

    /** @test */
    public function test_validate_venue_with_error()
    {
        $method = TestingUtils::getPrivateMethod($this->fd, 'validate_venue');
        $returned = $method->invoke($this->fd, "WRONG_VENUE");
        $this->assertEquals(false, $returned);
    }

    /** @test */
    public function test_validate_venue_without_error()
    {
        $method = TestingUtils::getPrivateMethod($this->fd, 'validate_venue');
        $returned = $method->invoke($this->fd, "HOME");
        $this->assertEquals(true, $returned);
    }


}
