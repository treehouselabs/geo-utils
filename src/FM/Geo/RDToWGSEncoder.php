<?php

namespace FM\Geo;

class RDToWGSEncoder
{
    /**
     * This function takes as parameters the values X and Y of the RD system,
     * and returns an array of WGS84 coordinates \phi, \lambda or lattitude and
     * longitude.
     *
     * The data in the transformational arrays is taken from
     * http://www.dekoepel.nl/pdf/Transformatieformules.pdf, which describes the
     * techniques followed to retrieve these values.
     *
     * @param  double $X
     * @param  double $Y
     * @return array
     */
    public static function encode($X, $Y)
    {
        // Base point Amersfoort
        $X0 = 155000.00;
        $Y0 = 463000.00;

        $lat0 = 52.15517440;
        $long0 = 5.38720621;

        $K = array(
            0 => array(
                1 => 3235.65389,
                2 => -0.24750,
                3 => -0.06550
            ),
            1 => array(
                0 => -0.00738,
                1 => -0.00012
            ),
            2 => array(
                0 => -32.58297,
                1 => -0.84978,
                2 => -0.01709,
                3 => -0.00039
            ),
            4 => array(
                0 => 0.00530,
                1 => 0.00033
            )
        );

        $L = array(
            0 => array(
                1 => 0.01199,
                2 => 0.00022
            ),
            1 => array(
                0 => 5260.52916,
                1 => 105.94684,
                2 => 2.45656,
                3 => 0.05594,
                4 => 0.00128,
            ),
            2 => array(
                0 => -0.00022
            ),
            3 => array(
                0 => -0.81885,
                1 => -0.05607,
                2 => -0.00256
            ),
            5 => array(
                0 => 0.00026
            )
        );

        $dX = ($X - $X0) / 100000;
        $dY = ($Y - $Y0) / 100000;

        $lat  = $lat0  + static::sumOver($K, $dX, $dY) / 3600;
        $long = $long0 + static::sumOver($L, $dX, $dY) / 3600;

        return array($lat, $long);
    }

    /**
     * Calculates \sigma_p \sigma_q A_pq \times dX^p \times dY^q
     * p are the first indices of A, q are the second
     *
     * @param  array  $A
     * @param  double $dX
     * @param  double $dY
     * @return double
     */
    public static function sumOver(array $A, $dX, $dY)
    {
        $result = 0;

        foreach ($A as $p => $qs) {
            foreach ($qs as $q => $val) {
                $result += $val * pow($dX, $p) * pow($dY, $q);
            }
        }

        return $result;
    }
}
