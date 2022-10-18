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

class remainingtimemodel extends \BUSaragon\common\model {
    public $_database_collection = 'bus_stop_remaining_time';
    public $code = '';
    public $date = '';
    /** @var array int => string */
    public $routes = [];
    public $lines = [];
    /** @var array route_id => H:i:s */
    public $times = [];

    public function update_times_from_api( $api_object ) {
        $ret = false;
        $this->_id = null;

        if ( isset( $api_object->TR ) ) {
            $date = date( 'Y-m-d' );
            $array_criteria[] = [ 'code', 'eq', $api_object->stop_id, 'string' ];
            $array_criteria[] = [ 'date', 'eq', $date, 'string' ];

            $array_obj = $this->get_all( $array_criteria, [], 0, 1 );
            if ( !empty( $array_obj ) ) {
                $saved_obj = reset( $array_obj );
                $this->set( $saved_obj );
            } else {
                $this->date = $date;
                $this->code = $api_object->stop_id;
            }

            if ( !isset( $this->lines[ $api_object->id_linea ] ) ) {
                $this->lines[ $api_object->id_linea ] = $api_object->name;
            }

            $route_id = array_search( $api_object->route, $this->routes );
            if ( $route_id === false ) {
                $this->routes[] = $api_object->route;
                $route_id = key( $this->routes );
                $this->times[ $route_id ] = [];
            }

            $time_id = array_search( $api_object->remaining_time, $this->times[ $route_id ] );
            if ( $time_id === false ) {
                $this->times[ $route_id ][] = $api_object->remaining_time;
                asort( $this->times[ $route_id ] );
            }

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
            [ 'date' => 1 ],
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
        $array_string = [ 'code', 'date', 'routes' ];
        foreach ( $array_string as $key ) {
            if ( is_array( $this->{$key} ) ) {
                foreach ( $this->{$key} as $val_key => $value ) {
                    $this->{$key}[ $val_key ] = (string) \call_user_func( $callback_function, $value );
                }
            } else {
                $this->{$key} = (string) \call_user_func( $callback_function, $this->{$key} );
            }
        }

        // Common attributes: booleans
        $array_boolean = [ 'active' ];
        foreach ( $array_boolean as $key ) {
            $this->{$key} = (boolean) $this->{$key};
        }
    }
}
