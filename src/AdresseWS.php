<?php
/*!
 * geonorge-adresse
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * Anders Evenrud <andersevenrud@gmail.com>
 */

namespace Geonorge;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;

use Geonorge\AdresseException;

use InvalidArgumentException;

/**
 * AdresseWS
 * @link http://ws.geonorge.no/AdresseWS/application.wadl
 * @author Anders Evenrud <andersevenrud@gmail.com>
 */
class AdresseWS
{
    const BASE_URI = 'http://ws.geonorge.no/AdresseWS/adresse/';

    /**
     * Perform a request against the API
     *
     * @param String $endpoint Endpoint
     * @param Array $args Query parameters
     * @param Boolean $debug Show debug information
     * @throws \Geonorge\AdresseException
     * @return Array
     */
    static protected function request($endpoint, Array $args = [], $debug = false)
    {

        $client = new Client([
            'base_uri' => static::BASE_URI
        ]);

        try {
            $response = $client->request('GET', $endpoint, [
                'debug' => $debug,
                'query' => $args ?: []
            ]);
        } catch ( ClientException $e ) {
            throw new AdresseException($e->getMessage());
        } catch ( ConnectException $e ) {
            throw new AdresseException($e->getMessage());
        }

        $body = (string) $response->getBody();
        if ( $data = json_decode($body) ) {
            if ( !$data->sokStatus->ok ) {
                throw new AdresseException($data->sokStatus->melding);
            }

            if ( $data->totaltAntallTreff > 0 ) {
                // This is only set when actual results are returned for some reason
                return $data->adresser;
            }
        } else {
            if ( $e = json_last_error_msg() ) {
                throw new AdresseException('Invalid JSON: ' . $e);
            } else {
                throw new AdresseException('Invalid JSON: ' . $data);
            }
        }

        return [];
    }

    /**
     * Search for adresses by string
     *
     * @since 0.5
     * @param String $query The string to query for
     * @param Integer $page Return the resulted page
     * @param Integer $perPage Results per page
     * @throws InvalidArgumentException
     * @throws \Geonorge\AdresseException
     * @return stdClass[]
     */
    static public function search($query, $page = 0, $perPage = 10)
    {
        $query = trim($query);

        if ( empty($query) ) {
            throw new InvalidArgumentException('Requires something to search for.');
        }

        return static::request('sok', [
            'sokestreng' => $query,
            'side' => $page,
            'antPerSide' => $perPage
        ]);
    }

    /**
     * Search for adresses by radius
     *
     * @since 0.5
     * @param Double $north North coordinate
     * @param Double $east East coordinate
     * @param Double $radius The radius
     * @param Integer $page Return the resulted page
     * @param Integer $perPage Results per page
     * @throws InvalidArgumentException
     * @throws \Geonorge\AdresseException
     * @return stdClass[]
     */
    static public function searchRadius($north, $east, $radius = 1.0, $page = 0, $perPage = 10)
    {
        return static::request('radius', [
            'nord' => (double) $north,
            'aust' => (double) $east,
            'radius' => (double) $radius,
            'side' => $page,
            'antPerSide' => $perPage
        ]);
    }

    /**
     * Search for adresses by bounding box
     *
     * @since 0.5
     * @param Double $northL North lower boundary
     * @param Double $eastL East lower boundary
     * @param Double $northU North upper boundary
     * @param Double $eastU East upper boundary
     * @param Integer $page Return the resulted page
     * @param Integer $perPage Results per page
     * @throws InvalidArgumentException
     * @throws \Geonorge\AdresseException
     * @return stdClass[]
     */
    static public function searchBox($northL, $eastL, $northU, $eastU, $page = 0, $perPage = 10)
    {
        return static::request('radius', [
            'nordLL' => (double) $northL,
            'austLL' => (double) $eastL,
            'nordUR' => (double) $northU,
            'austUR' => (double) $eastU,
            'side' => $page,
            'antPerSide' => $perPage
        ]);
    }

}
