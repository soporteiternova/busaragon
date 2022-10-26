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
 * Bus stop controller
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
    public function actions( $action = '' ) {
        if ( $action === '' ) {
            $action = \BUSaragon\common\controller::get( 'action' );
        }
        switch ( $action ) {
            case 'crondaemon':
                return $this->crondaemon();
                break;
            case 'get_remaining_time':
                return $this->remaining_time();
                break;
            case 'tab_listing':
                return $this->tab_listing();
                break;
            case 'routes_listing':
                return $this->routes_listing();
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
        $minute = (int) date( 'i' );
        if ( $minute >= 0 && $minute <= 5 ) {
            $array_endpoints = [ \BUSaragon\common\controller::ENDPOINT_BUS_STOP_ARAGON, \BUSaragon\common\controller::ENDPOINT_BUS_STOP_CTAZ ];
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

            // Remaining times for CTAZ bus stop
            set_time_limit( 120 );
            $api_url = \BUSaragon\common\controller::get_endpoint_url( \BUSaragon\common\controller::ENDPOINT_BUS_STOP_REMAINING_TIMES_CTAZ );
            $array_objs = json_decode( file_get_contents( $api_url ) );

            $bus_stop_times_obj = new remainingtimemodel();

            if ( !empty( $array_objs ) ) {
                foreach ( $array_objs as $obj ) {
                    $bus_stop_times_obj->update_times_from_api( $obj );
                }
            }
        }

        // Routes
        $array_endpoints = [ \BUSaragon\common\controller::ENDOPOINT_BUS_ROUTES_ARAGON, \BUSaragon\common\controller::ENDOPOINT_BUS_ROUTES_CTAZ ];
        $bus_routes_obj = new modelroutes();

        foreach ( $array_endpoints as $endpoint ) {
            $api_url = \BUSaragon\common\controller::get_endpoint_url( $endpoint );
            $array_objs = json_decode( file_get_contents( $api_url ) );
            if ( !empty( $array_objs ) ) {
                foreach ( $array_objs as $obj ) {
                    $bus_routes_obj->update_from_api( $obj, $endpoint );
                }
            }
            var_dump( 'Updated ' . count( $array_objs ) . ' routes for ' . $endpoint );
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

    /**
     * Show tab listing with bus stop
     * @return string
     * @throws \Exception
     */
    private function tab_listing() {
        $obj_busstop = new model();
        $array_criteria[] = [ 'active', 'eq', true, 'bool' ];
        $array_obj_busstop = $obj_busstop->get_all( $array_criteria );

        $str_return = '<table class="default"><thead><tr><td>Localidad</td><td>Direcci&oacute;n</td></tr></thead><tbody>';
        $array_data = [];
        foreach ( $array_obj_busstop as $obj_busstop ) {
            $array_data[ $obj_busstop->city ] = [ $obj_busstop->city, $obj_busstop->address ];
        }
        ksort( $array_data );
        foreach ( $array_data as $data ) {
            $str_return .= '<tr><td>' . \BUSaragon\common\utils::detect_utf8( $data[ 0 ] ) . '</td><td>' . \BUSaragon\common\utils::detect_utf8( $data[ 1 ] ) . '</td></tr>';
        }
        $str_return .= '</tbody></table';
        return $str_return;
    }

    /**
     * Shows routes tab listing
     * @return string
     * @throws \Exception
     */
    private function routes_listing() {
        $obj_route = new modelroutes();
        $array_criteria[] = [ 'active', 'eq', true, 'bool' ];
        $array_obj_routes = $obj_route->get_all( $array_criteria );

        $str_return = '<table class="default"><thead><tr><td>Ruta</td><td>Origen</td><td>Destino</td></tr></thead><tbody>';
        $array_data = [];
        foreach ( $array_obj_routes as $obj_route ) {
            $array_data[ $obj_route->network . '_' . $obj_route->name ] = [ $obj_route->name, $obj_route->origin, $obj_route->destination, (int) $obj_route->network ];
        }
        ksort( $array_data );
        $current_network = -1;
        foreach ( $array_data as $data ) {
            if ( $data[ 3 ] !== $current_network ) {
                $str_return .= '<tr><td colspan="3" style="text-align: center;"><b>' . ( $data[ 3 ] === \BUSaragon\common\controller::ENDOPOINT_BUS_ROUTES_ARAGON ? 'GOBIERNO DE ARAG&Oacute;N' : 'CTAZ' ) . '</b></td></tr>';
                $current_network = $data[ 3 ];
            }
            $str_return .= '<tr><td>' . \BUSaragon\common\utils::detect_utf8( $data[ 0 ] ) . '</td><td>' . \BUSaragon\common\utils::detect_utf8( $data[ 1 ] ) . '</td><td>' . \BUSaragon\common\utils::detect_utf8( $data[ 2 ] ) . '</td></tr>';
        }
        $str_return .= '</tbody></table';
        return $str_return;
    }

    private function remaining_time() {
        $bus_stop_id = \BUSaragon\common\controller::get( 'bus_stop_id' );

        $obj_remainingtime = new remainingtimemodel();
        return $obj_remainingtime->get_busstop_times( $bus_stop_id );
    }
}
