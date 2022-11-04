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

        if ( isset( $api_object->stop_id ) ) {
            $date = date( 'Y-m-d' );

            $array_criteria[] = [ 'code', 'eq', $api_object->stop_id, 'string' ];
            $array_criteria[] = [ 'date', 'eq', $date, 'string' ];

            $array_obj = $this->get_all( $array_criteria, [], 0, 1 );
            if ( !empty( $array_obj ) ) {
                $saved_obj = reset( $array_obj );
                $this->set( \BUSaragon\common\utils::cast_object_to_array( $saved_obj ) );
            } else {
                $this->date = $date;
                $this->code = $api_object->stop_id;
            }

            if ( !isset( $this->lines[ $api_object->id_linea ] ) ) {
                $this->lines[ $api_object->id_linea ] = $api_object->name;
                $this->routes[ $api_object->id_linea ] = [];
                $this->times[ $api_object->id_linea ] = [];
            }

            if ( !isset( $this->routes[ $api_object->id_linea ][ $api_object->id ] ) ) {
                $this->routes[ $api_object->id_linea ][ $api_object->id ] = $api_object->route;
                $this->times[ $api_object->id_linea ][ $api_object->id ] = [];
            }

            $time_id = array_search( $api_object->remaining_time, $this->times[ $api_object->id_linea ][ $api_object->id ] );
            if ( $time_id === false ) {
                $this->times[ $api_object->id_linea ][ $api_object->id ][] = $api_object->remaining_time;
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
        $array_integer = [];// [ 'icon', 'type', 'loc_contract', 'priority' ];
        foreach ( $array_integer as $key ) {
            $this->{$key} = (integer) $this->{$key};
        }

        // Common attributes: string
        $array_string = [ 'code', 'date', 'lines', 'routes' ];
        foreach ( $array_string as $key ) {
            if ( is_array( $this->{$key} ) ) {
                foreach ( $this->{$key} as $val_key => $value ) {
                    if ( is_array( $this->{$key}[ $val_key ] ) ) {
                        foreach ( $this->{$key}[ $val_key ] as $val_key2 => $value2 ) {
                            $this->{$key}[ $val_key ][ $val_key2 ] = (string) \call_user_func( $callback_function, $value2 );
                        }
                    } else {
                        $this->{$key}[ $val_key ] = (string) \call_user_func( $callback_function, $value );
                    }
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

    /**
     * Gets last time arriving for a given bus stop passed by OpenData code
     *
     * @param string $code bus stop OpenData identificator
     * @param string $str_return default return if it doesn't exist any remaining time
     *
     * @return string
     */
    public function get_busstop_times( $code, $str_return = '' ) {
        $array_criteria[] = [ 'code', 'eq', $code, 'string' ];
        $array_times = $this->get_all( $array_criteria, [ 'date' => -1 ], 0, 1 );

        if ( !empty( $array_times ) ) {
            $stop_times = reset( $array_times );
            $str_return = $this->get_formated_times( $stop_times );
        }

        return $str_return;
    }

    /**
     * Returns arriving bus stop times in html format to be showed to the user
     *
     * @param model $obj_remainingtimes
     *
     * @return string
     */
    private function get_formated_times( $obj_remainingtimes = null ) {
        if ( !is_null( $obj_remainingtimes ) ) {
            $this->set( \BUSaragon\common\utils::cast_object_to_array( $obj_remainingtimes ) );
        }

        $str_return = '';
        $date = time();
        foreach ( $this->times as $line_id => $routes ) {
            $str_return .= '<div style="max-height: 200px;overflow: scroll;"><table class="default"><thead><td>' . $this->lines[ $line_id ] . '</td></thead><tbody>';
            foreach ( $routes as $route_id => $times ) {
                $str_return .= '<tr><td>' . $this->routes[ $line_id ][ $route_id ] . '</td></tr>';
                if ( !empty( $this->times[ $line_id ][ $route_id ] ) ) {
                    foreach ( $this->times[ $line_id ][ $route_id ] as $time ) {
                        $timeseconds = strtotime( $time );
                        if ( $timeseconds >= $date ) {
                            $str_return .= '<tr><td>' . \BUSaragon\common\databasemongo::datetime_mongodate( $time, false, false ) . '</td></tr>';
                        }
                    }
                }
            }
            $str_return .= '</tbody></table></div>';
        }

        return $str_return;
    }
}
