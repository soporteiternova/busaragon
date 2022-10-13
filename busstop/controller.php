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
 * Database controller
 * @author ITERNOVA (info@iternova.net)
 * @version 1.0.0 - 20221001
 * @package busstop
 * @copyright 2022 ITERNOVA SL
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace BUSaragon\busstop;

class controller {
    /**
     * Action controller for busstop class
     * @return bool
     */
    public function actions() {
        $action = \BUSaragon\common\controller::get( 'action' );
        switch ( $action ) {
            case 'crondaemon':
                return $this->crondaemon();
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
        // First, we get bus stop listing for all Aragon
        $array_endpoints = [ \BUSaragon\common\controller::ENDPOINT_BUS_STOP_ARAGON, \BUSaragon\common\controller::ENDPOINT_BUS_STOP_ZARAGOZA ];
        $bus_stop_obj = new model();

        foreach ( $array_endpoints as $endpoint ) {
            $api_url = \BUSaragon\common\controller::get_endpoint_url( $endpoint );
            $array_objs = json_decode( file_get_contents( $api_url ) );

            if ( !empty( $array_objs ) ) {
                foreach ( $array_objs as $obj ) {
                    $bus_stop_obj->update_from_api( $obj );
                }
            }
        }

        return true;
    }

    /**
     * Shows bus stop in map
     * @return string
     */
    private function listing() {
        $obj_busstop = new model();
        $array_markers = $obj_busstop->get_array_markers();
        return \BUSaragon\common\map::create_map( $array_markers, 100, 800, true );
    }
}
