<?php

namespace Helium\IdpClient\Tests;

use Helium\IdpClient\Helpers\Jwt;
use Orchestra\Testbench\TestCase;

class JwtTest extends TestCase
{
    protected $exampleJwt = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c";

    public function testStaticBuilders()
    {
        $this->assertInstanceOf(Jwt::class, Jwt::of($this->exampleJwt));
        $this->assertInstanceOf(Jwt::class, Jwt::read($this->exampleJwt));
    }

    public function testDecode()
    {
        $jwt = Jwt::of($this->exampleJwt);

        $header = $jwt->header();
        $this->assertIsArray($header);
        $this->assertArrayHasKey('alg', $header);
        $this->assertEquals('HS256', $header['alg']);
        $this->assertArrayHasKey('typ', $header);
        $this->assertEquals('JWT', $header['typ']);

        $payload = $jwt->payload();
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('sub', $payload);
        $this->assertEquals('1234567890', $payload['sub']);
        $this->assertArrayHasKey('name', $payload);
        $this->assertEquals('John Doe', $payload['name']);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertEquals(1516239022, $payload['iat']);
    }
}
