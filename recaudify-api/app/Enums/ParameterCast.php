<?php

namespace App\Enums;

enum ParameterCast: string
{
    case Boolean = "boolean";
    case Integer = "integer";
    case Float = "float";
    case String = "string";
    case Json = "json";
}
