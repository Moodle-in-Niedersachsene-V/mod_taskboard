<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_taskboard\local;

/**
 * Turns PeerTube video addresses into embed addresses, for hosts the administrator allowed.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class video_embed {
    /**
     * Returns the hosts from the plugin setting, lower case.
     *
     * @return string[]
     */
    public static function allowed_hosts(): array {
        $setting = (string)get_config('mod_taskboard', 'peertubehosts');
        $hosts = [];
        foreach (preg_split('/[\s,]+/', \core_text::strtolower($setting)) as $host) {
            if ($host !== '' && preg_match('/^[a-z0-9.-]+(:\d+)?$/', $host)) {
                $hosts[] = $host;
            }
        }
        return $hosts;
    }

    /**
     * Returns the embed address for a video address, or null if it is not an allowed PeerTube video.
     *
     * Accepts /w/<id>, /videos/watch/<id> and /videos/embed/<id> on an allowed host over https.
     *
     * @param string $url The address entered on the card.
     * @return string|null
     */
    public static function embed_url(string $url): ?string {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        $parts = parse_url($url);
        if (!$parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            return null;
        }
        $host = \core_text::strtolower($parts['host']) . (isset($parts['port']) ? ':' . (int)$parts['port'] : '');
        if (!in_array($host, self::allowed_hosts(), true)) {
            return null;
        }
        if (!preg_match('~^/(?:w|videos/watch|videos/embed)/([A-Za-z0-9-]{6,64})/?$~', $parts['path'] ?? '', $match)) {
            return null;
        }
        return 'https://' . $host . '/videos/embed/' . $match[1];
    }

    /**
     * Whether a video address is acceptable for a card.
     *
     * @param string $url The address entered on the card.
     * @return bool
     */
    public static function is_valid(string $url): bool {
        return self::embed_url($url) !== null;
    }
}
