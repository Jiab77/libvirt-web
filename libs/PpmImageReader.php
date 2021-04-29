<?php
/**
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * PHP version 5
 * @copyright  Ingmar Decker
 * @author     Ingmar Decker <http://www.webdecker.de>
 */

 /** Class to read ppm, pnm, pgm, pbm images.
   */
class PpmImageReader {

    public function PpmImageReader() {
    }

    public function getType() {
        return 'ppm';
    }

    /** Returns true iff this reader can read given file (at least file extension should be
      * checked.
      * @return bool True if reader can read file, false otherwise
      */
    public function canRead($filename) {
        $pi = pathinfo($filename);
        $ext = strtolower($pi['extension']);
        return ($ext == 'ppm' || $ext == 'pbm' || $ext == 'pgm');
    }

    /** Reads file and returns array with 6 elements: type name, image resource, with, height, dpi for x, dpi for y.
      * Dpi values might be 0 if file format does not provide dpi or other resolution information.
      */
    public function read($filename) {
        $type = $this->getType();
        $r = array($type, null, 0, 0, 0, 0);
        $image = null;
        try {
            $image = $this->readPnm($filename);
        } catch (Exception $e) {
            // ignore
        }
        if ($image) {
            $w = imagesx($image);
            $h = imagesy($image);
            $r = array($type, $image, $w, $h, 0, 0);
        }
        return $r;
    }

    private function readPnm($filename) {
        $magic = '';
        $width = 0;
        $height = 0;
        $image = null;
        $max = 1;
        $buf = '';

        $file = @fopen($filename, "rb");
        if ($file) {
            // First 2 bytes define the type of the image...
            $magic = fread ($file, 2);
            if (!in_array($magic, array('P1', 'P2', 'P3', 'P4', 'P5', 'P6'))) throw new Exception('No pnm file - "[' . ord($buf[0]) . '][' . ord($buf[1]) . ']" is not a valid magic number.');

            // Skip next until line with width and height...
            $buf = $this->readLine($file); // Rest of first line
            $buf = $this->readLine($file); // Comment?

            // while (ereg('^#', $buf)) {
            while (preg_match('/^#/m', $buf) !== 0) {
                $buf = $this->readLine($file); // Skip comments and get next line
            }

            $tokensNeeded = ($magic == 1 || $magic == 4 ? 2 : 3);
            $tokens = array();
            do {
                $tokens = array_merge($tokens, preg_split("/[ \t]+/", $buf));
                $buf = $this->readLine($file);
                #print "DO BUF: $buf <br />\n";
            } while (!feof($file) && count($tokens) < $tokensNeeded);

            // Now "push back" last line...
            fseek($file, 0 - strlen($buf), SEEK_CUR);

            $width = trim($tokens[0]);
            $height = trim($tokens[1]);
            if (count($tokens) > 2) $max = $tokens[2];
            if ($max > 255) throw new Exception("Unable to read ppm with max value > 255 ($max)");


            $image = imagecreatetruecolor($width, $height);

            if ($magic == 'P6') {
                // P6: each pixel as 3 bytes (r, g, b 1 byte each)...
                for ($y = 0; $y < $height && !feof($file); $y++) {
                    for ($x = 0; $x < $width; $x++) {
                        $rgb = @unpack('N', chr(0) . fread($file, 3));
                        if ($rgb) {
                            $rgb = $rgb[1];
                            imagesetpixel($image, $x, $y, $rgb);
                        }
                    }
                }
            } else if ($magic == 'P5') {
                // P5: each pixel 1 byte (grey value)...
                for ($y = 0; $y < $height && !feof($file); $y++) {
                    for ($x = 0; $x < $width; $x++) {
                        $b = fread ($file, 1);
                        $rgb = $b << 16 | $b << 8 | $b;
                        imagesetpixel($image, $x, $y, $rgb);
                    }
                }
            } else if ($magic == 'P4') {
                // Bitmap - each pixel is one bit (1=black, 0=white)...
                $x = 0;
                $y = 0;
                while ($y < $height) {
                    $b = fread($file, 1);
                    $mask = 128;
                    for ($i = 0; $i < 8; $i++) {
                        $r = $b & $mask;
                        $r = ($r > 0 ? 0 : 255);
                        $rgb = $r << 16 | $r << 8 | $r;
                        imagesetpixel($image, $x, $y, $rgb);
                        $mask >> 1;
                        $x++;
                        if ($x > $width) {
                            $x = 0;
                            $y++;
                        }
                    }
                }
            } else if ($magic == 'P3') {
                for ($y = 0; $y < $height && !feof($file); $y++) {
                    for ($x = 0; $x < $width; $x++) {
                        // todo: $max  !?
                        $r = intval($this->readNextToken($file)); #  / $max * 255
                        $g = intval($this->readNextToken($file));
                        $b = intval($this->readNextToken($file));
                        $rgb = $r << 16 | $g << 8 | $b;
                        imagesetpixel($image, $x, $y, $rgb);
                    }
                }
            } else if ($magic == 'P2') {
                for ($y = 0; $y < $height && !feof($file); $y++) {
                    for ($x = 0; $x < $width; $x++) {
                        $r = intval($this->readNextToken($file)); #  / $max * 255
                        $rgb = $r << 16 | $r << 8 | $r;
                        imagesetpixel($image, $x, $y, $rgb);
                    }
                }
            } else if ($magic == 'P1') {
                for ($y = 0; $y < $height && !feof($file); $y++) {
                    for ($x = 0; $x < $width; $x++) {
                        $b = fread ($file, 1);
                        $r = ($b > 0 ? 0 : 255);
                        $rgb = $r << 16 | $r << 8 | $r;
                        imagesetpixel($image, $x, $y, $rgb);
                    }
                }
            }

            fclose ($file);
        }
        return $image;
    }

    /** Reads bytes of file until LineFeed character. Returns string including LF. */
    static function readLine($file) {
        $buf = '';
        $b = '';
        while (($b = fread ($file, 1)) !== '') {
            $buf .= $b;
            if (ord($b) == 10) break;
        }
        return $buf;
    }

    /** Read until whitespace. Returns read bytes.
      * @param $file File handle
      */
    private function readToken($file) {
        $buf = '';
        while (($b = fread ($file, 1)) !== '') {
            // if (ereg("[ \t\n\r]", $b)) break;
            if (preg_match("[ \t\n\r]", $b) === 1) break;
            $buf .= $b;
        }
        if (!feof($file)) fseek($file, -1, SEEK_CUR);
        return $buf;
    }

    private function readWhite($file) {
        $buf = '';
        while (($b = fread ($file, 1)) !== '') {
            // if (!ereg("[ \t\n\r]", $b)) break;
            if (preg_match("[ \t\n\r]", $b) === 0) break;
            $buf .= $b;
        }
        if (!feof($file)) fseek($file, -1, SEEK_CUR);
        return $buf;
    }

    private function readNextToken($file) {
        $s = $this->readWhite($file); // Skip whitespace
        return $this->readToken($file);
    }

}

?>