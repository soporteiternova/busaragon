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
 * Vehicle traveled distance data model
 * @author ITERNOVA (info@iternova.net)
 * @version 1.0.0 - 20221001
 * @package vehicles
 * @copyright 2022 ITERNOVA SL
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace BUSaragon\vehicles;

class modeldistance extends \BUSaragon\common\model {
    public $_database_collection = 'vehicles_distance';
    public $date = '';

    public $code = '';
    public $name = '';
    public $registration = 0;
    public $group = '';
    public $odometer_type = '';

    public $array_distance = [];
    public $network = -1;

    /**
     * Actualiza la posicion lat/lng de los vehiculos
     *
     * @param $api_object
     * @param $api_endpoint string endpoint identifier (Aragon, CTAZ)
     *
     * @return bool
     */
    public function update_from_api( $api_object, $api_endpoint ) {
        $this->_id = null;
        $ret = false;

        if ( $api_endpoint === \BUSaragon\common\controller::ENDOPOINT_BUS_VEHICLES_DISTANCE_ARAGON ) {
            $array_criteria[] = [ 'code', 'eq', $api_object->assetId, 'string' ];
            $array_criteria[] = [ 'network', 'eq', $api_endpoint, 'int' ];

            $array_obj = $this->get_all( $array_criteria, [], 0, 1 );

            if ( !empty( $array_obj ) ) {
                $saved_obj = reset( $array_obj );
                $this->_id = $saved_obj->_id;
                $this->set( $saved_obj );
            } else {
                $this->set_attr_from_api( $api_object, $api_endpoint );
            }

            if ( array_search( $api_object->distance, $this->array_distance ) === false ) {
                $this->array_distance[] = $api_object->distance;
                sort( $this->array_distance );
                $ret = $this->store();
            }
        }

        return $ret;
    }

    /**
     * Sets common attributes
     *
     * @param $api_obj \stdClass object from OpenData
     * @param $date string date in Y-m-d format
     *
     * @return boolean
     */
    private function set_attr_from_api( $api_obj, $api_endpoint ) {
        $array_attr = [];
        if ( $api_endpoint === \BUSaragon\common\controller::ENDOPOINT_BUS_VEHICLES_DISTANCE_ARAGON ) {
            $array_attr = [
                'group' => $api_obj->groupName,
                'code' => $api_obj->assetId,
                'name' => $api_obj->assetName,
                'registration' => $api_obj->assetRegistration,
                'network' => $api_endpoint,
                'odometer_type' => $api_obj->odometerType,
            ];
        }
        foreach ( $array_attr as $attr => $value ) {
            $this->{$attr} = $value;
        }
        return $this->store();
    }

    /**
     * Sets collection indexes
     * @return bool Resultado de la operacion
     * @throws \Exception
     */
    protected function ensureIndex() {
        $array_indexes = [
            [ 'code' => 1 ],
            [ 'name' => 1 ],
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
        $array_integer = [ 'registration', 'network' ];
        foreach ( $array_integer as $key ) {
            $this->{$key} = (integer) $this->{$key};
        }

        // Common attributes: float
        $array_float = [ 'array_distance' ];
        foreach ( $array_float as $attr ) {
            foreach ( $this->{$attr} as $key => $value ) {
                $this->{$attr}[ $key ] = (float) $value;
            }
        }

        // Common attributes: string
        $array_string = [ 'group', 'code', 'name', 'odometer_type' ];
        foreach ( $array_string as $key ) {
            $this->{$key} = (string) \call_user_func( $callback_function, $this->{$key} );
        }

        // Common attributes: booleans
        $array_boolean = [ 'active' ];
        foreach ( $array_boolean as $key ) {
            $this->{$key} = (boolean) $this->{$key};
        }
    }

    /**
     * Returns string with last distance for a given bus
     *
     * @param $bus_id
     *
     * @return string
     */
    public function get_distance( $bus_id ) {
        $str_return = '';
        if ( !empty( $bus_id ) ) {
            $array_criteria[] = [ 'code', 'eq', $bus_id, 'string' ];
            $array_criteria[] = [ 'active', 'eq', true, 'bool' ];
            $array_obj = $this->get_all( $array_criteria, [], 0, 1 );
            if ( !empty( $array_obj ) ) {
                $obj = reset( $array_obj );
                if ( !empty( $obj->array_distance ) ) {
                    $str_return = '<ul><li>' . end( $obj->array_distance ) . ' km</li></ul>';
                }
            }
        }

        return $str_return;
    }
}
