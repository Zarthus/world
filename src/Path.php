<?php

declare(strict_types=1);

namespace Zarthus\World\App;

final class Path
{
    public static function root(): string
    {
        return dirname(__DIR__);
    }

    public static function www(bool $dev): string
    {
        return $dev ? self::private() : self::public();
    }

    // The development resources dir (raw, uncompiled templates and scripts).
    private static function private(): string
    {
        return self::root() . '/private';
    }

    // The production resources dir (compiled css, js, templates, ..)
    private static function public(): string
    {
        return self::root() . '/public';
    }

    public static function tmp(): string
    {
        return self::root() . '/tmp';
    }

    public static function lib(): string
    {
        return self::root() . '/lib';
    }

    public static function app(): string
    {
        return self::root() . '/src';
    }

    public static function tests(): string
    {
        return self::root() . '/tests';
    }

    public static function articles(bool $input): string
    {
        return self::www($input) . '/articles';
    }

    public static function templates(bool $input): string
    {
        return self::www($input) . ($input ? '/html' : '/');
    }

    public static function assets(bool $input): string
    {
        return self::www($input) . '/assets';
    }

    public static function css(bool $input): string
    {
        $name = $input ? 'scss' : 'css';
        return self::www($input) . '/' . $name;
    }
}
