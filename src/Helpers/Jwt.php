<?php

namespace Helium\IdpClient\Helpers;

use Illuminate\Support\Str;

class Jwt
{
    protected $header;
    protected $payload;

    public function __construct(array $header, array $payload)
    {
        $this->header = $header;
        $this->payload = $payload;
    }

    public static function of(string $token): Jwt
    {
        return self::read($token);
    }

    public static function read(string $token): Jwt
    {
        $token = Str::of($token)->split('/\s/')->last();
        $parts = Str::of($token)->split('/\./');

        $header = json_decode(base64_decode($parts[0]), true);
        $payload = json_decode(base64_decode($parts[1]), true);

        return new static($header, $payload);
    }

    public function header(): array
    {
        return $this->header;
    }

    public function payload(): array
    {
        return $this->payload;
    }
}