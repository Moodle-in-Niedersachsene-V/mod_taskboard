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
 * Stores card images safely: re-encodes them, which drops EXIF data such as location, and limits their size.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class image_processor {
    /** @var int Longest edge of a stored card image in pixels. */
    public const MAX_EDGE = 1600;

    /** @var string[] Accepted image types. */
    public const MIMETYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /**
     * Returns the maximum upload size for card images in bytes.
     *
     * @param \stdClass $course The course record.
     * @return int
     */
    public static function max_bytes(\stdClass $course): int {
        global $CFG;
        $setting = (int)get_config('mod_taskboard', 'maxbytes');
        return (int)get_user_max_upload_file_size(
            \core\context\course::instance($course->id),
            $CFG->maxbytes,
            $course->maxbytes,
            $setting
        );
    }

    /**
     * Whether every file in a draft area is an image the plugin can store.
     *
     * Lets the card form reject unreadable files instead of dropping them silently on save.
     *
     * @param int $draftitemid The draft item id of the current user.
     * @return bool
     */
    public static function draft_images_readable(int $draftitemid): bool {
        global $USER;
        $context = \core\context\user::instance($USER->id);
        $files = get_file_storage()->get_area_files($context->id, 'user', 'draft', $draftitemid, 'id', false);
        foreach ($files as $file) {
            if (!in_array($file->get_mimetype(), self::MIMETYPES, true) || !$file->get_imageinfo()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Re-encodes the image of a card after it was saved from a draft area.
     *
     * Keeps at most one image; anything that is not a readable image is removed.
     *
     * @param \context $context The module context.
     * @param int $cardid The card id.
     * @return bool Whether the card has an image afterwards.
     */
    public static function sanitise_card_image(\context $context, int $cardid): bool {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_taskboard', 'cardimage', $cardid, 'timemodified DESC, id DESC', false);
        $kept = false;
        foreach ($files as $file) {
            if ($kept || !in_array($file->get_mimetype(), self::MIMETYPES, true) || !$file->get_imageinfo()) {
                $file->delete();
                continue;
            }
            self::reencode($file);
            $kept = true;
        }
        return $kept;
    }

    /**
     * Stores an image sent as base64 data (for example pasted from the clipboard) as the card image.
     *
     * @param \context $context The module context.
     * @param int $cardid The card id.
     * @param string $base64 The image data, base64 encoded, optionally as data URL.
     * @param int $maxbytes Maximum size of the decoded image in bytes.
     * @return void
     */
    public static function store_base64(\context $context, int $cardid, string $base64, int $maxbytes): void {
        if (preg_match('~^data:[a-z/+.-]+;base64,~i', $base64)) {
            $base64 = substr($base64, strpos($base64, ',') + 1);
        }
        $content = base64_decode($base64, true);
        if ($content === false || $content === '') {
            throw new \moodle_exception('invalidimage', 'mod_taskboard');
        }
        if ($maxbytes > 0 && strlen($content) > $maxbytes) {
            throw new \moodle_exception('imagetoolarge', 'mod_taskboard', '', display_size($maxbytes));
        }
        $mimetype = (new \finfo(FILEINFO_MIME_TYPE))->buffer($content);
        $info = in_array($mimetype, self::MIMETYPES, true) ? getimagesizefromstring($content) : false;
        if (!$info || !in_array($info['mime'], self::MIMETYPES, true)) {
            throw new \moodle_exception('invalidimage', 'mod_taskboard');
        }
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_taskboard', 'cardimage', $cardid);
        $extension = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ][$info['mime']];
        $file = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'mod_taskboard',
            'filearea' => 'cardimage',
            'itemid' => $cardid,
            'filepath' => '/',
            'filename' => 'pasted.' . $extension,
        ], $content);
        self::reencode($file);
    }

    /**
     * Replaces a stored image by a re-encoded copy without metadata, scaled down if needed.
     *
     * JPEG stays JPEG; every other type becomes PNG, since GD writes no metadata into either.
     *
     * @param \stored_file $file The stored image.
     * @return \stored_file The new file.
     */
    private static function reencode(\stored_file $file): \stored_file {
        $info = $file->get_imageinfo();
        $width = (int)$info['width'];
        $height = (int)$info['height'];
        $newwidth = null;
        $newheight = null;
        if (max($width, $height) > self::MAX_EDGE) {
            if ($width >= $height) {
                $newwidth = self::MAX_EDGE;
            } else {
                $newheight = self::MAX_EDGE;
            }
        }
        $isjpeg = $file->get_mimetype() === 'image/jpeg';
        $basename = pathinfo($file->get_filename(), PATHINFO_FILENAME);
        $record = [
            'contextid' => $file->get_contextid(),
            'component' => $file->get_component(),
            'filearea' => $file->get_filearea(),
            'itemid' => $file->get_itemid(),
            'filepath' => '/',
            'filename' => clean_filename(($basename !== '' ? $basename : 'image') . '-' . time() . ($isjpeg ? '.jpg' : '.png')),
            'mimetype' => $isjpeg ? 'image/jpeg' : 'image/png',
        ];
        $fs = get_file_storage();
        $new = $fs->convert_image($record, $file, $newwidth, $newheight, true, $isjpeg ? 85 : 6);
        $file->delete();
        return $new;
    }
}
