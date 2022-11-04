<?php
/**
 * BUSaragon - ITERNOVA <info@iternova.net>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Bus stop data model
 * @author ITERNOVA (info@iternova.net)
 * @version 1.0.0 - 20221001
 * @package busstop
 * @copyright 2022 ITERNOVA SL
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace BUSaragon\busstop;

class model extends \BUSaragon\common\model {
    public $_database_collection = 'bus_stop';
    public $code = '';
    public $address = '';
    public $city = '';
    public $lat_lng = [];
    public $network = -1;

    /**
     * Updates a bus stop object from open data api, and creates it if doesn't exist
     *
     * @param $api_object
     *
     * @return bool
     */
    public function update_from_api( $api_object ) {
        $ret = false;
        $this->_id = null;
        if ( isset( $api_object->cod_parada ) ) {
            $array_criteria[] = [ 'code', 'eq', $api_object->cod_parada, 'string' ];
            $array_criteria[] = [ 'network', 'eq', \BUSaragon\common\controller::ENDPOINT_BUS_STOP_ARAGON, 'int' ];

            $array_obj = $this->get_all( $array_criteria, [], 0, 1 );
            if ( !empty( $array_obj ) ) {
                $saved_obj = reset( $array_obj );
                $this->_id = $saved_obj->_id;
            }
            $this->network = \BUSaragon\common\controller::ENDPOINT_BUS_STOP_ARAGON;
            $this->code = $api_object->cod_parada;
            if ( isset( $api_object->nucleo ) ) {
                $this->city = $api_object->nucleo;
            }
            if ( isset( $api_object->DENO_DIRECCIÓN ) ) {
                $this->address = $api_object->DENO_DIRECCIÓN;
            }

            $this->lat_lng = \BUSaragon\common\utils::OSGB36ToWGS84( $api_object->y, $api_object->x );
            $ret = $this->store();
        } elseif ( isset( $api_object->stop_id ) ) {
            $array_criteria[] = [ 'code', 'eq', $api_object->stop_id, 'string' ];
            $array_criteria[] = [ 'network', 'eq', \BUSaragon\common\controller::ENDPOINT_BUS_STOP_CTAZ, 'int' ];

            $array_obj = $this->get_all( $array_criteria, [], 0, 1 );
            if ( !empty( $array_obj ) ) {
                $saved_obj = reset( $array_obj );
                $this->_id = $saved_obj->_id;
            }
            $this->network = \BUSaragon\common\controller::ENDPOINT_BUS_STOP_CTAZ;
            $this->code = $api_object->stop_id;
            if ( isset( $api_object->nucleo ) ) {
                $this->city = $api_object->nucleo;
            }
            if ( isset( $api_object->stop_name ) ) {
                $this->address = $api_object->stop_name;
            }

            $this->lat_lng = [ $api_object->stop_lat, $api_object->stop_lon ];
            $ret = $this->store();
        }

        return $ret;
    }

    /**
     * Sets collection indexes
     * @return bool Resultado de la operacion
     * @throws \Exception
     */
    protected function ensureIndex() {
        $array_indexes = [
            [ 'city' => 1 ],
            [ 'lat_lng' => '2d' ],
        ];
        foreach ( $array_indexes as $index ) {
            $this->_database_controller->ensureIndex( $this->_database_collection, $index );
        }
        return true;
    }

    /**
     * Cofieds object to utf8/iso8859-1
     *
     * @param boolean $to_utf8 if true, converts to utf8, if false, converts to iso8859-1
     *
     * @return void
     */
    public function object_encode_data( $to_utf8 = false ) {
        $callback_function = \BUSaragon\common\utils::class . ( $to_utf8 ? '::detect_utf8' : '::detect_iso8859_1' );
        $to_utf8 = true;
        // Dates (format \MongoDate en UTC+0)
        $array_fields_datetime = [ 'updated_at', 'created_at' ];
        foreach ( $array_fields_datetime as $key ) {
            $this->{$key} = \BUSaragon\common\databasemongo::datetime_mongodate( $this->{$key}, $to_utf8, false );
        }

        // Common attributes: integer
        $array_integer = [ 'network' ];// [ 'icon', 'type', 'loc_contract', 'priority' ];
        foreach ( $array_integer as $key ) {
            $this->{$key} = (integer) $this->{$key};
        }

        // Common attributes: string
        $array_string = [ 'code', 'address', 'city' ];
        foreach ( $array_string as $key ) {
            $this->{$key} = (string) \call_user_func( $callback_function, $this->{$key} );
        }
        $this->lat_lng = [ (float) $this->lat_lng[ 0 ], (float) $this->lat_lng[ 1 ] ];

        // Common attributes: booleans
        $array_boolean = [ 'active' ];
        foreach ( $array_boolean as $key ) {
            $this->{$key} = (boolean) $this->{$key};
        }
    }

    /**
     * Gets array of data to be showed as markers in maps
     *
     * @param array $array_criteria search criteria
     *
     * @return array
     * @throws \Exception
     */
    public function get_array_markers( $array_criteria = [] ) {
        $array_objs = $this->get_all( $array_criteria );
        $array_return = [];
        $server_url = \BUSaragon\common\utils::get_server_url();

        foreach ( $array_objs as $obj ) {
            $str_address = htmlspecialchars( $obj->address );
            $array_return[] = [
                'id' => $obj->_id,
                'lat' => $obj->lat_lng[ 0 ],
                'lng' => $obj->lat_lng[ 1 ],
                'title' => $str_address,
                'marker_color' => $obj->network === \BUSaragon\common\controller::ENDPOINT_BUS_STOP_ARAGON ? 'red' : 'green',
                'url' => $obj->network === \BUSaragon\common\controller::ENDPOINT_BUS_STOP_CTAZ ? $server_url . '/?&zone=bus_stop&action=get_remaining_time&bus_stop_id=' . $obj->code : '',
            ];
        }

        return $array_return;
    }
}
