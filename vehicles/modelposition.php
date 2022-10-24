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
 * Vehicle position data model
 * @author ITERNOVA (info@iternova.net)
 * @version 1.0.0 - 20221001
 * @package busstop
 * @copyright 2022 ITERNOVA SL
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace BUSaragon\vehicles;

class modelposition extends \BUSaragon\common\model {
    public $_database_collection = 'vehicles_position';
    public $date = '';
    public $group_id = '';
    public $group = '';
    public $code = '';
    public $name = '';
    public $registration = 0;
    public $type = '';

    public $array_date = [];
    public $array_city = [];
    public $array_country = [];
    public $array_driver_group_id = [];
    public $array_driver_group_name = [];
    public $array_driver_id = [];
    public $array_driver_name = [];
    public $array_engine_total_hours = [];
    public $array_engine_total_hours_type = [];
    public $array_event_type = [];
    public $array_formatted_address = [];
    public $array_heading = [];
    public $array_latlng = [];
    public $array_odometer = [];
    public $array_post_code = [];
    public $array_road = [];
    public $array_road_number = [];
    public $array_speed = [];
    public $array_status = [];
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

        if ( $api_endpoint === \BUSaragon\common\controller::ENDOPOINT_BUS_VEHICLES_POSITION_ARAGON ) {
            $date = date( 'Y-m-d', strtotime( $api_object->date ) );
            $array_criteria[] = [ 'date', 'eq', $date, 'string' ];
            $array_criteria[] = [ 'code', 'eq', $api_object->assetId, 'string' ];
            $array_criteria[] = [ 'network', 'eq', $api_endpoint, 'int' ];

            $array_obj = $this->get_all( $array_criteria, [], 0, 1 );

            if ( !empty( $array_obj ) ) {
                $saved_obj = reset( $array_obj );
                $this->_id = $saved_obj->_id;
                $this->set( $saved_obj );
            } else {
                $this->set_attr_from_api( $api_object, $date, $api_endpoint );
            }

            $array_attr = [
                'date' => 'array_date',
                'city' => 'array_city',
                'country' => 'array_country',
                'driverGroupId' => 'array_driver_group_id',
                'driverGroupName' => 'array_driver_group_name',
                'driverId' => 'array_driver_id',
                'driverName' => 'array_driver_name',
                'engineTotalHours' => 'array_engine_total_hours',
                'engineTotalHoursType' => 'array_engine_total_hours_type',
                'eventType' => 'array_event_type',
                'formattedAddress' => 'array_formatted_address',
                'heading' => 'array_heading',
                'odometer' => 'array_odometer',
                'postCode' => 'array_post_code',
                'road' => 'array_road',
                'roadNumber' => 'array_road_number',
                'speed' => 'array_speed',
                'status' => 'array_status',
            ];

            foreach ( $array_attr as $api_attr => $obj_attr ) {
                $this->{$obj_attr}[] = $api_object->{$api_attr};
            }

            $this->array_latlng[] = [ $api_object->latitude, $api_object->longitude ];
        } else {
            // CTAZ poositions
            $date = date( 'Y-m-d', strtotime( $api_object->momento ) );
            $array_criteria[] = [ 'date', 'eq', $date, 'string' ];
            $array_criteria[] = [ 'code', 'eq', $api_object->bus, 'string' ];

            $array_obj = $this->get_all( $array_criteria, [], 0, 1 );

            if ( !empty( $array_obj ) ) {
                $saved_obj = reset( $array_obj );
                $this->_id = $saved_obj->_id;
                $this->set( $saved_obj );
            } else {
                $this->set_attr_from_api( $api_object, $date, $api_endpoint );
            }

            $array_attr = [
                'momento' => 'array_date',
                'linea' => 'array_driver_group_id',
                'nombre_linea' => 'array_driver_group_name',
            ];

            foreach ( $array_attr as $api_attr => $obj_attr ) {
                $this->{$obj_attr}[] = $api_object->{$api_attr};
            }

            $this->array_latlng[] = [ $api_object->latitud, $api_object->longitud ];
        }

        return $this->store();
    }

    /**
     * Sets common attributes
     *
     * @param $api_obj \stdClass object from OpenData
     * @param $date string date in Y-m-d format
     *
     * @return boolean
     */
    private function set_attr_from_api( $api_obj, $date, $api_endpoint ) {
        if ( $api_endpoint === \BUSaragon\common\controller::ENDOPOINT_BUS_VEHICLES_POSITION_ARAGON ) {
            $array_attr = [
                'date' => $date,
                'group_id' => $api_obj->assetGroupId,
                'group' => $api_obj->assetGroupName,
                'code' => $api_obj->assetId,
                'name' => $api_obj->assetName,
                'registration' => $api_obj->assetRegistration,
                'type' => $api_obj->assetType,
            ];
        } else {
            $array_attr = [
                'date' => $date,
                'code' => $api_obj->bus,
                'name' => $api_obj->bus,
            ];
        }
        foreach ( $array_attr as $attr => $value ) {
            $this->{$attr} = $value;
        }
        $this->network = $api_endpoint;
        return $this->store();
    }

    /**
     * Sets collection indexes
     * @return bool Resultado de la operacion
     * @throws \Exception
     */
    protected function ensureIndex() {
        $array_indexes = [
            [ 'date' => 1 ],
            [ 'code' => 1 ],
            [ 'name' => 1 ],
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

        // Dates (format \MongoDate en UTC+0)
        $array_fields_datetime = [ 'updated_at', 'created_at' ];
        foreach ( $array_fields_datetime as $key ) {
            $this->{$key} = \BUSaragon\common\databasemongo::datetime_mongodate( $this->{$key}, $to_utf8, false );
        }
        foreach ( $this->array_date as $key => $value ) {
            $this->array_date[ $key ] = \BUSaragon\common\databasemongo::datetime_mongodate( $value, $to_utf8, false );
        }

        // Common attributes: integer
        $array_integer = [ 'registration', 'network' ];
        foreach ( $array_integer as $key ) {
            $this->{$key} = (integer) $this->{$key};
        }

        // Common attributes: float
        $array_float = [ 'array_engine_total_hours', 'array_heading', 'array_odometer', 'array_speed' ];
        foreach ( $array_float as $attr ) {
            foreach ( $this->{$attr} as $key => $value ) {
                $this->{$attr}[ $key ] = (float) $value;
            }
        }
        foreach ( $this->array_latlng as $key => $latlng ) {
            $this->array_latlng[ $key ] = [ (float) $latlng[ 0 ], (float) $latlng[ 1 ] ];
        }

        // Common attributes: string
        $array_string = [ 'date', 'group_id', 'group', 'code', 'name', 'type' ];
        foreach ( $array_string as $key ) {
            $this->{$key} = (string) \call_user_func( $callback_function, $this->{$key} );
        }
        $array_string = [ 'array_city', 'array_country', 'array_driver_group_id', 'array_driver_group_name', 'array_driver_id', 'array_driver_name', 'array_engine_total_hours_type', 'array_event_type', 'array_formatted_address', 'array_post_code', 'array_road', 'array_road_number', 'array_status' ];
        foreach ( $array_string as $attr ) {
            foreach ( $this->{$attr} as $key => $value ) {
                $this->{$attr}[ $key ] = (string) \call_user_func( $callback_function, $value );
            }
        }

        // Common attributes: booleans
        $array_boolean = [ 'active' ];
        foreach ( $array_boolean as $key ) {
            $this->{$key} = (boolean) $this->{$key};
        }
    }
}
