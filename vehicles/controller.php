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
 * Vehicles controller
 * @author ITERNOVA (info@iternova.net)
 * @version 1.0.0 - 20221001
 * @package busstop
 * @copyright 2022 ITERNOVA SL
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace BUSaragon\vehicles;

class controller {
    /**
     * Action controller for busstop class
     * @return bool
     */
    public function actions( $action = '' ) {
        if ( $action === '' ) {
            $action = \BUSaragon\common\controller::get( 'action' );
        }
        switch ( $action ) {
            case 'crondaemon':
                return $this->crondaemon();
                break;
            case 'get_historic':
                return $this->get_historic();
                break;
            case 'listing':
            default:
                return $this->listing();
                break;
        }

        return true;
    }

    /**
     * Gets bus stop list from opendata repository.
     * @return bool
     */
    protected function crondaemon() {
        // Loading of vehicles listing
        $minute = (int) date( 'i' );
        if ( $minute >= 0 && $minute <= 5 ) {
            $api_url = \BUSaragon\common\controller::get_endpoint_url( \BUSaragon\common\controller::ENDOPOINT_BUS_VEHICLES_ARAGON );
            $array_objs = json_decode( file_get_contents( $api_url ) );
            $obj_vehicle = new model();

            if ( !empty( $array_objs ) ) {
                foreach ( $array_objs as $obj ) {
                    $obj_vehicle->update_from_api( $obj );
                }
            }
        }

        // Loading last positions
        $array_api_url = [ \BUSaragon\common\controller::ENDOPOINT_BUS_VEHICLES_POSITION_ARAGON => \BUSaragon\common\controller::get_endpoint_url( \BUSaragon\common\controller::ENDOPOINT_BUS_VEHICLES_POSITION_ARAGON ),
            \BUSaragon\common\controller::ENDOPOINT_BUS_VEHICLES_POSITION_CTAZ => \BUSaragon\common\controller::get_endpoint_url( \BUSaragon\common\controller::ENDOPOINT_BUS_VEHICLES_POSITION_CTAZ ) ];

        foreach ( $array_api_url as $api_endpoint => $api_url ) {
            $array_objs = json_decode( file_get_contents( $api_url ) );
            $n_updated = 0;
            if ( !empty( $array_objs ) ) {
                foreach ( $array_objs as $obj ) {
                    $obj_vehicle_position = new modelposition();
                    if ( $obj_vehicle_position->update_from_api( $obj, $api_endpoint ) ) {
                        $n_updated++;
                    }
                }
            }
            var_dump( 'Updated ' . $n_updated . ' positions for ' . $api_endpoint );
        }

        if ( $minute >= 5 && $minute <= 7 ) {
            // Loading historic traveling
            $array_objs = json_decode( file_get_contents( \BUSaragon\common\controller::get_endpoint_url( \BUSaragon\common\controller::ENDOPOINT_BUS_VEHICLES_HISTORIC_ARAGON ) ) );
            $n_updated = 0;
            if ( !empty( $array_objs ) ) {
                foreach ( $array_objs as $obj ) {
                    $obj_vehicle_position = new modelhistoric();
                    if ( $obj_vehicle_position->update_from_api( $obj, \BUSaragon\common\controller::ENDOPOINT_BUS_VEHICLES_HISTORIC_ARAGON ) ) {
                        $n_updated++;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Shows bus in map
     * @return string
     */
    private function listing() {
        $obj_bus = new modelposition();
        $array_markers = $obj_bus->get_array_markers();
        return \BUSaragon\common\map::create_map( $array_markers, 100, 800, true );
    }

    private function get_historic() {
        $obj_historic = new modelhistoric();
        return $obj_historic->get_historic( \BUSaragon\common\controller::get( 'bus_id' ) );
    }
}
