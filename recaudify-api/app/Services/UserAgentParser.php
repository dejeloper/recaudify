<?php

namespace App\Services;

class UserAgentParser
{
    public function parseOs(string $ua): array
    {
        $rules = [
            "/Windows NT ([\d.]+)/" => "Windows",
            "/iPhone OS ([\d_]+)/" => "iOS",
            "/iPad.*OS ([\d_]+)/" => "iPadOS",
            "/Android ([\d.]+)/" => "Android",
            "/Mac OS X ([\d_]+)/" => "macOS",
            "/Linux/" => "Linux",
        ];

        foreach ($rules as $regex => $name) {
            if (preg_match($regex, $ua, $m)) {
                return ["name" => $name, "version" => str_replace("_", ".", $m[1] ?? "")];
            }
        }

        return ["name" => "Unknown", "version" => ""];
    }

    public function parseDeviceType(string $ua): string
    {
        if (preg_match("/iPad|Android(?!.*Mobile)|Tablet/i", $ua)) {
            return "tablet";
        }

        if (preg_match("/Mobile|Android|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i", $ua)) {
            return "mobile";
        }

        return "desktop";
    }
}
