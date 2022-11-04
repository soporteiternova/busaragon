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
 * Routes data model
 * @author ITERNOVA (info@iternova.net)
 * @version 1.0.0 - 20221001
 * @package busstop
 * @copyright 2022 ITERNOVA SL
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace BUSaragon\busstop;

class modelroutes extends \BUSaragon\common\model {
    public $_database_collection = 'routes';
    public $code = '';
    public $concesion_code = '';
    public $name = '';
    public $origin = '';
    public $destination = '';
    public $network = -1;

    public function update_from_api( $api_object, $api_endpoint ) {
        $ret = false;
        $this->_id = null;

        if ( $api_endpoint === \BUSaragon\common\controller::ENDOPOINT_BUS_ROUTES_ARAGON && isset( $api_object->cod_ruta ) ) {
            $array_criteria[] = [ 'code', 'eq', $api_object->cod_ruta, 'string' ];
            $array_criteria[] = [ 'network', 'eq', $api_endpoint, 'int' ];

            $array_obj = $this->get_all( $array_criteria, [], 0, 1 );
            if ( !empty( $array_obj ) ) {
                $saved_obj = reset( $array_obj );
                $this->set( \BUSaragon\common\utils::cast_object_to_array( $saved_obj ) );
            } else {
                $this->code = $api_object->cod_ruta;
            }

            $this->concesion_code = $api_object->cod_concesion;
            $this->name = $api_object->deno_ruta;
            $this->origin = $api_object->origen;
            $this->destination = $api_object->destino;
            $this->network = $api_endpoint;

            $ret = $this->store();
        } elseif ( $api_endpoint === \BUSaragon\common\controller::ENDOPOINT_BUS_ROUTES_CTAZ ) {
            $array_criteria[] = [ 'code', 'eq', $api_object->line_id, 'string' ];
            $array_criteria[] = [ 'network', 'eq', $api_endpoint, 'int' ];

            $array_obj = $this->get_all( $array_criteria, [], 0, 1 );
            if ( !empty( $array_obj ) ) {
                $saved_obj = reset( $array_obj );
                $this->set( \BUSaragon\common\utils::cast_object_to_array( $saved_obj ) );
            } else {
                $this->code = $api_object->line_id;
            }

            $this->concesion_code = -1;
            $this->name = $api_object->name;
            $this->origin = $api_object->from;
            $this->destination = $api_object->to;
            $this->network = $api_endpoint;

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
            [ 'code' => 1 ],
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
        $callback_function = \BUSaragon\common\utils::class . ( $to_utf8 ? '::detect_utf8' : '::detect_utf8' );

        // Dates (format \MongoDate en UTC+0)
        $array_fields_datetime = [ 'updated_at', 'created_at' ];
        foreach ( $array_fields_datetime as $key ) {
            $this->{$key} = \BUSaragon\common\databasemongo::datetime_mongodate( $this->{$key}, $to_utf8, false );
        }

        // Common attributes: string
        $array_string = [ 'code', 'concesion_code', 'name', 'origin', 'destination' ];
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
        $this->network = (int) $this->network;

        // Common attributes: booleans
        $array_boolean = [ 'active' ];
        foreach ( $array_boolean as $key ) {
            $this->{$key} = (boolean) $this->{$key};
        }
    }
}
