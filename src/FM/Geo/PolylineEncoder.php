<?php

namespace FM\Geo;

/**
 * This class is used to encode a number of coordinates into an encoded polyline
 * to be used in Google maps.
 *
 * @see http://www.svennerberg.com/examples/polylines/PolylineEncoder.php.txt
 */
class PolylineEncoder
{
    protected $numLevels;
    protected $zoomFactor;
    protected $verySmall;
    protected $forceEndpoints;
    protected $zoomLevelBreaks;

    /**
     * Encodes a number of coordinates into an encoded polyline to be used in
     * Google maps.
     *
     * Be sure to use the same numLevels and zoomFactor in your Javascript or
     * the lines won't display properly.
     *
     * @param array $points Array containing the coordinates for the
     *                                 polyline
     * @param integer $numLevels Indicates how many different levels of
     *                                 magnification the polyline has
     * @param integer $zoomFactor The change in magnification between the
     *                                 levels supplied via $numLevels
     * @param float $verySmall Indicates the length of a barely visible
     *                                 object at the highest zoom level. The
     *                                 default value is 0.00001. By lowering
     *                                 this number you can decrease the number
     *                                 of coordinates used.
     * @param boolean $forceEndpoints Indicates whether or not the endpoints
     *                                 should be visible at all zoom levels.
     *                                 Defaults to true.
     * @return array Associative array containing the encoded
     *                                 points, the encoded levels, an escaped
     *                                 string literal containing the encoded
     *                                 points, the zoom factor and the number
     *                                 of levels.
     */
    public function encode(array $points, $numLevels = 18, $zoomFactor = 2, $verySmall = 0.00001, $forceEndpoints = true)
    {
        $this->numLevels = $numLevels;
        $this->zoomFactor = $zoomFactor;
        $this->verySmall = $verySmall;
        $this->forceEndpoints = $forceEndpoints;

        $this->zoomLevelBreaks = array();
        for ($i = 0; $i < $this->numLevels; $i++) {
            $this->zoomLevelBreaks[$i] = $this->verySmall * pow($this->zoomFactor, $this->numLevels - $i - 1);
        }

        if (count($points) > 2) {
            $stack[] = array(0, count($points) - 1);
            while (count($stack) > 0) {
                $current = array_pop($stack);
                $maxDist = 0;
                for ($i = $current[0] + 1; $i < $current[1]; $i++) {
                    $temp = $this->distance($points[$i], $points[$current[0]], $points[$current[1]]);
                    if ($temp > $maxDist) {
                        $maxDist = $temp;
                        $maxLoc = $i;
                        if ($maxDist > $absMaxDist) {
                            $absMaxDist = $maxDist;
                        }
                    }
                }
                if ($maxDist > $this->verySmall) {
                    $dists[$maxLoc] = $maxDist;
                    array_push($stack, array($current[0], $maxLoc));
                    array_push($stack, array($maxLoc, $current[1]));
                }
            }
        }

        $encodedPoints = $this->createEncodings($points, $dists);
        $encodedLevels = $this->encodeLevels($points, $dists, $absMaxDist);
        $encodedPointsLiteral = str_replace('\\', "\\\\", $encodedPoints);

        return array(
            'points'         => $encodedPoints,
            'levels'         => $encodedLevels,
            'points_literal' => $encodedPointsLiteral,
            'zoom_factor'    => $this->zoomFactor,
            'num_levels'     => $this->numLevels
        );
    }

    protected function computeLevel($dd)
    {
        if ($dd > $this->verySmall) {
            $lev = 0;
            while ($dd < $this->zoomLevelBreaks[$lev]) {
                $lev++;
            }
        }

        return $lev;
    }

    protected function distance($p0, $p1, $p2)
    {
        if ($p1[0] == $p2[0] && $p1[1] == $p2[1]) {
            $out = sqrt(pow($p2[0] - $p0[0], 2) + pow($p2[1] - $p0[1], 2));
        } else {
            $u = (($p0[0] - $p1[0]) * ($p2[0] - $p1[0]) + ($p0[1] - $p1[1]) * ($p2[1] - $p1[1])) / (pow($p2[0] - $p1[0], 2) + pow($p2[1] - $p1[1], 2));
            if ($u <= 0) {
                $out = sqrt(pow($p0[0] - $p1[0], 2) + pow($p0[1] - $p1[1], 2));
            }
            if ($u >= 1) {
                $out = sqrt(pow($p0[0] - $p2[0], 2) + pow($p0[1] - $p2[1], 2));
            }
            if (0 < $u && $u < 1) {
                $out = sqrt(pow($p0[0] - $p1[0] - $u * ($p2[0] - $p1[0]), 2) + pow($p0[1] - $p1[1] - $u * ($p2[1] - $p1[1]), 2));
            }
        }

        return $out;
    }

    protected function encodeSignedNumber($num)
    {
       $sgn_num = $num << 1;
       if ($num < 0) {
           $sgn_num = ~($sgn_num);
       }

       return $this->encodeNumber($sgn_num);
    }

    protected function createEncodings($points, $dists)
    {
        for ($i = 0; $i < count($points); $i++) {
            if (isset($dists[$i]) || $i == 0 || $i == count($points) - 1) {
                $point = $points[$i];
                $lat = $point[0];
                $lng = $point[1];
                $late5 = floor($lat * 1e5);
                $lnge5 = floor($lng * 1e5);
                $dlat = $late5 - $plat;
                $dlng = $lnge5 - $plng;
                $plat = $late5;
                $plng = $lnge5;
                $encoded_points .= $this->encodeSignedNumber($dlat) . $this->encodeSignedNumber($dlng);
            }
        }

        return $encoded_points;
    }

    protected function encodeLevels($points, $dists, $absMaxDist)
    {
        if ($this->forceEndpoints) {
            $encoded_levels .= $this->encodeNumber($this->numLevels - 1);
        } else {
            $encoded_levels .= $this->encodeNumber($this->numLevels - $this->computeLevel($absMaxDist) - 1);
        }

        for ($i = 1; $i < count($points) - 1; $i++) {
            if (isset($dists[$i])) {
                $encoded_levels .= $this->encodeNumber($this->numLevels - $this->computeLevel($dists[$i]) - 1);
            }
        }

        if ($this->forceEndpoints) {
            $encoded_levels .= $this->encodeNumber($this->numLevels - 1);
        } else {
            $encoded_levels .= $this->encodeNumber($this->numLevels - $this->computeLevel($absMaxDist) - 1);
        }

        return $encoded_levels;
    }

    protected function encodeNumber($num)
    {
        while ($num >= 0x20) {
            $nextValue = (0x20 | ($num & 0x1f)) + 63;
            $encodeString .= chr($nextValue);
            $num >>= 5;
        }

        $finalValue = $num + 63;
        $encodeString .= chr($finalValue);

        return $encodeString;
    }
}
