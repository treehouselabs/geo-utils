<?php

namespace FM\Geo;

class PointUtils
{
    /**
     * Checks if a point lies within a polygon, using the point-in-polygon
     * algorithm.
     *
     * @see http://assemblysys.com/php-point-in-polygon-algorithm/
     *
     * @param  array   $point
     * @param  array   $polygon
     * @return boolean
     */
    public static function pointInPolygon(array $point, array $polygon)
    {
        // normalize point
        $point = static::normalizePoint($point);

        // normalize polygon to vertices
        $vertices = array();
        foreach ($polygon as $vertex) {
            $vertices[] = static::normalizePoint($vertex);
        }

        // Check if the point sits exactly on a vertex
        if (static::pointOnVertex($point, $vertices) === true) {
            return true;
        }

        // Check if the point is inside the polygon or on the boundary
        $intersections = 0;

        for ($i = 1; $i < sizeof($vertices); $i++) {

            $vertex1 = $vertices[$i - 1];
            $vertex2 = $vertices[$i];

            // check if point is on an horizontal polygon boundary
            if ($vertex1[1] == $vertex2[1] && $vertex1[1] == $point[1] && $point[0] > min($vertex1[0], $vertex2[0]) && $point[0] < max($vertex1[0], $vertex2[0])) {
                return true;
            }

            if ($point[1] > min($vertex1[1], $vertex2[1]) && $point[1] <= max($vertex1[1], $vertex2[1]) && $point[0] <= max($vertex1[0], $vertex2[0]) && $vertex1[1] != $vertex2[1]) {

                $xinters = ($point[1] - $vertex1[1]) * ($vertex2[0] - $vertex1[0]) / ($vertex2[1] - $vertex1[1]) + $vertex1[0];

                // check if point is on the polygon boundary (other than horizontal)
                if ($xinters == $point[0]) {
                    return true;
                }

                if ($vertex1[0] == $vertex2[0] || $point[0] <= $xinters) {
                    $intersections++;
                }
            }
        }

        // if the number of edges we passed through is odd, then it's in the polygon.
        return ($intersections % 2 != 0);
    }

    /**
     * Checks if a point sits exactly on a vertex
     *
     * @param  array   $point
     * @param  array   $vertices
     * @return boolean
     */
    public static function pointOnVertex($point, $vertices)
    {
        foreach ($vertices as $vertex) {
            if ($point == $vertex) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalizes input to a point
     *
     * @param mixed $inp The point to normalize, accepted inputs:
     *                    - A string of two numbers, separated by either a space
     *                      or comma
     *                    - An array containing two numbers
     * @return array            An array with 2 doubles (lat/lng)
     * @throws RuntimeException When input could not be normalized
     */
    public static function normalizePoint($inp)
    {
        $point = array();

        if (is_string($inp)) {

            $parts = array();

            if (strpos($inp, ' ') !== false) {
                $parts = explode(' ', $inp);
            } elseif (strpos($inp, ',') !== false) {
                $parts = explode(',', $inp);
            }

            if (sizeof($parts) === 2) {
                $point[0] = (double) $parts[0];
                $point[1] = (double) $parts[1];
            }
        } elseif (is_array($inp)) {
            if (sizeof($inp) === 2) {
                $values = array_values($inp);
                $point[0] = (double) $values[0];
                $point[1] = (double) $values[1];
            }
        }

        if (sizeof($point) !== 2) {
            throw new \RuntimeException(sprintf('Input needs to be a string of two doubles, separated by either a space or comma, or an array containing two doubles. You supplied "%s"', $inp));
        }

        return $point;
    }
}
