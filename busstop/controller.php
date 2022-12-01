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
            case 'cities_listing':
                return $this->cities_listing();
                break;
            case 'get_city_destinations':
                return $this->get_city_destinations();
                break;
            case 'get_route_info':
                return $this->get_route_info();
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

            // Routes
            $array_endpoints = [ \BUSaragon\common\controller::ENDOPOINT_BUS_ROUTES_ARAGON, \BUSaragon\common\controller::ENDOPOINT_BUS_ROUTES_CTAZ ];

            foreach ( $array_endpoints as $endpoint ) {
                $api_url = \BUSaragon\common\controller::get_endpoint_url( $endpoint );
                $array_objs = json_decode( file_get_contents( $api_url ) );
                if ( !empty( $array_objs ) ) {
                    foreach ( $array_objs as $obj ) {
                        $bus_routes_obj = new modelroutes();
                        $bus_routes_obj->update_from_api( $obj, $endpoint );
                    }
                }
                var_dump( 'Updated ' . count( $array_objs ) . ' routes for ' . $endpoint );
            }

            // Cities
            $endpoint = \BUSaragon\common\controller::ENDOPOINT_BUS_CITIES_CTAZ;
            $api_url = \BUSaragon\common\controller::get_endpoint_url( $endpoint );
            $array_objs = json_decode( file_get_contents( $api_url ) );

            if ( !empty( $array_objs ) ) {
                foreach ( $array_objs as $obj ) {
                    $cities_model = new modelcities();
                    $cities_model->update_from_api( $obj, $endpoint );
                }

                // Actualizamos del gobierno de aragon....
                $obj_busstop = new model();
                $obj_city = new modelcities();
                $array_criteria_busstop[] = [ 'network', 'eq', \BUSaragon\common\controller::ENDPOINT_BUS_STOP_ARAGON, 'int' ];
                $array_busstop = $obj_busstop->get_all( $array_criteria_busstop );
                foreach ( $array_busstop as $busstop ) {
                    $array_criteria_city[ 'city' ] = [ 'origin', 'eq', ucfirst( $busstop->city ), 'string' ];
                    $n_cities = $obj_city->get_all( $array_criteria_city );
                    if ( count( $n_cities ) === 0 ) {
                        $city = new modelcities();
                        $city->network = \BUSaragon\common\controller::ENDOPOINT_BUS_CITIES_ARAGON;
                        $city->code = $busstop->code;
                        $city->origin = $busstop->city;
                        $city->store();
                    }
                }

                var_dump( 'Updated ' . count( $array_objs ) . ' cities' );
            }

            // Cities destination
            $endpoint = \BUSaragon\common\controller::ENDOPOINT_BUS_CITIES_DESTINATION_CTAZ;
            $api_url = \BUSaragon\common\controller::get_endpoint_url( $endpoint );
            $array_objs = json_decode( file_get_contents( $api_url ) );

            if ( !empty( $array_objs ) ) {
                foreach ( $array_objs as $obj ) {
                    $cities_destination_model = new modeldestinations();
                    $cities_destination_model->update_from_api( $obj, $endpoint );
                }
                var_dump( 'Updated ' . count( $array_objs ) . ' cities destinations' );
            }
        }

        // Remaining times for CTAZ bus stop
        set_time_limit( 120 );
        $api_url = \BUSaragon\common\controller::get_endpoint_url( \BUSaragon\common\controller::ENDPOINT_BUS_STOP_REMAINING_TIMES_CTAZ );
        $array_objs = json_decode( file_get_contents( $api_url ) );

        if ( !empty( $array_objs ) ) {
            foreach ( $array_objs as $obj ) {
                $bus_stop_times_obj = new remainingtimemodel();
                $bus_stop_times_obj->update_times_from_api( $obj );
            }
        }

        // Busstop secuence for routes
        $api_url = \BUSaragon\common\controller::get_endpoint_url( \BUSaragon\common\controller::ENDOPOINT_BUS_ROUTES_BUSSTOP_SECUENCE_CTAZ );
        $array_objs = json_decode( file_get_contents( $api_url ) );

        if ( !empty( $array_objs ) ) {
            foreach ( $array_objs as $obj ) {
                $bus_stop_secuence_obj = new modelroutesbusstopsecuence();
                $bus_stop_secuence_obj->update_from_api( $obj, \BUSaragon\common\controller::ENDOPOINT_BUS_ROUTES_BUSSTOP_SECUENCE_CTAZ );
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
        return \BUSaragon\common\map::create_map( $array_markers, 100, 800, true, true );
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

        $array_data = [];
        foreach ( $array_obj_busstop as $obj_busstop ) {
            $array_data[] = [ 'city' => $obj_busstop->city, 'address' => $obj_busstop->address ];
        }

        $str_return = \Jupitern\Table\Table::instance()
                                           ->setData( $array_data )
                                           ->attr( 'table', 'id', 'busstop_table' )
                                           ->attr( 'table', 'class', 'default' )
                                           ->column()
                                           ->title( 'Localidad' )
                                           ->value( 'city' )
                                           ->add()
                                           ->column()
                                           ->title( 'Direcc&oacute;n' )
                                           ->value( 'address' )
                                           ->add()
                                           ->render( true );
        $str_return .= "<script type=\"text/javascript\">
                            \$(document).ready( function () {
                                \$('#busstop_table').DataTable();
                            });
                        </script>";
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

        $array_network = [ \BUSaragon\common\controller::ENDOPOINT_BUS_ROUTES_ARAGON => 'Arag&oacute;n', \BUSaragon\common\controller::ENDOPOINT_BUS_ROUTES_CTAZ => 'CTAZ' ];
        $array_data = [];
        $url_routes_info_partial = \BUSaragon\common\utils::get_server_url() . '/?zone=routes&action=get_route_info&code=';
        foreach ( $array_obj_routes as $obj_route ) {
            $url_route_info = $url_routes_info_partial . $obj_route->code;
            $str_name = $obj_route->network === \BUSaragon\common\controller::ENDOPOINT_BUS_ROUTES_CTAZ ? '<a href="javascript:void(0);" onclick="$(\'#route_info_dialog\').dialog(\'open\');$(\'#route_info_dialog\').load(\'' . $url_route_info . '\');">' . $obj_route->name . '</a>' : $obj_route->name;
            $array_data[] = [ 'name' => $str_name, 'origin' => $obj_route->origin, 'destination' => $obj_route->destination, 'network' => $array_network[ (int) $obj_route->network ] ];
        }

        $str_return = '<h2>Listado de rutas</h2>Para las rutas correspondientes a la red del CTAZ, es posible consultar las paradas de las mismas, pinchando sobre el nombre de la ruta.<br/><br/>';

        $str_return .= \Jupitern\Table\Table::instance()
                                            ->setData( $array_data )
                                            ->attr( 'table', 'id', 'routes_table' )
                                            ->attr( 'table', 'class', 'default' )
                                            ->column()
                                            ->title( 'Red' )
                                            ->value( 'network' )
                                            ->add()
                                            ->column()
                                            ->title( 'Ruta' )
                                            ->value( 'name' )
                                            ->add()
                                            ->column()
                                            ->title( 'Origen' )
                                            ->value( 'origin' )
                                            ->add()
                                            ->column()
                                            ->title( 'Destino' )
                                            ->value( 'destination' )
                                            ->add()
                                            ->render( true );
        $str_return .= '<div id="route_info_dialog" style="" title="Informaci&oacute;n de la ruta"></div>';
        $str_return .= "<script type=\"text/javascript\">
                            \$(document).ready( function () {
                                \$('#routes_table').DataTable();
                            \$('#route_info_dialog').dialog();
                            \$('#route_info_dialog').dialog('close');
                            });
                        </script>";

        return $str_return;
    }

    /**
     * Shows cities tab listing
     * @return string
     * @throws \Exception
     */
    private function cities_listing() {
        $obj_city = new modelcities();
        $array_criteria[] = [ 'active', 'eq', true, 'bool' ];
        $array_obj_cities = $obj_city->get_all( $array_criteria );

        $array_data = [];
        $url_destinations_partial = \BUSaragon\common\utils::get_server_url() . '/?zone=routes&action=get_city_destinations&code=';
        $str_return = '<h2>Listado de municipios</h2>Para los municipios de la red CTAZ se puede consultar, adem&aacute;s, posibles municipios de destino.<br/><br/>';
        foreach ( $array_obj_cities as $obj_city ) {
            $url_destinations = $url_destinations_partial . $obj_city->code . '&network=' . $obj_city->network;
            if ( $obj_city->network === \BUSaragon\common\controller::ENDOPOINT_BUS_CITIES_CTAZ ) {
                $str_origin = '<a href="javascript:void(0);" onclick="$(\'#destination_dialog\').dialog(\'open\');$(\'#destination_dialog\').load(\'' . $url_destinations . '\');">' . $obj_city->origin . '</a>';
            } else {
                $str_origin = $obj_city->origin;
            }
            $array_data[] = [ 'origin' => $str_origin, 'network' => (int) $obj_city->network ];
        }

        $str_return .= \Jupitern\Table\Table::instance()
                                            ->setData( $array_data )
                                            ->attr( 'table', 'id', 'cities_table' )
                                            ->attr( 'table', 'class', 'default' )
                                            ->column()
                                            ->title( 'Municipio' )
                                            ->value( 'origin' )
                                            ->add()
                                            ->render( true );

        $str_return .= '<div id="destination_dialog" class="ui-dialog" style="background:#FFFFFF;border:5px grey;" title="Destinos"></div>';
        $str_return .= "<script type=\"text/javascript\">
                            \$(document).ready( function () {
                                \$('#cities_table').DataTable();
                            });
                            \$('#destination_dialog').dialog();
                            \$('#destination_dialog').dialog('close');
                        </script>";
        return $str_return;
    }

    private function remaining_time() {
        $bus_stop_id = \BUSaragon\common\controller::get( 'bus_stop_id' );

        $obj_remainingtime = new remainingtimemodel();
        return $obj_remainingtime->get_busstop_times( $bus_stop_id );
    }

    /**
     * Returns destinations for a given city
     * @return string
     */
    private function get_city_destinations() {
        $str_return = '';
        $code = \BUSaragon\common\controller::get( 'code' );
        $network = \BUSaragon\common\controller::get( 'network' );
        $array_criteria[ 'code' ] = [ 'code', 'eq', $code, 'int' ];
        $array_criteria[] = [ 'active', 'eq', true, 'bool' ];
        $obj_destinations = new modeldestinations();
        $array_destinations = $obj_destinations->get_all( $array_criteria );
        $array_destinations_id = [];
        foreach ( $array_destinations as $obj_destination ) {
            $array_destinations_id[] = $obj_destination->code_origin;
        }

        $obj_city = new modelcities();
        $array_criteria_current[] = [ 'code', 'eq', $code, 'int' ];
        $array_criteria_current[] = [ 'network', 'eq', $network, 'int' ];
        $array_cities = $obj_city->get_all( $array_criteria_current );

        if ( !empty( $array_cities ) ) {
            $obj_city = reset( $array_cities );
            $str_return .= '<h4>' . $obj_city->origin . '</h4>';
        }
        $array_criteria_cities[] = [ 'code', 'in', $array_destinations_id, 'int' ];
        $array_criteria_cities[] = [ 'active', 'eq', true, 'bool' ];
        $array_criteria_cities[] = [ 'network', 'eq', $obj_city->network, 'int' ];

        $array_destinations = $obj_city->get_all( $array_criteria_cities );
        if ( !empty( $array_destinations ) ) {
            $str_return .= '<div style="max-height: 500px;overflow-y:auto; padding:10px;"><ul>';
            $str_return .= '<ul>';
            foreach ( $array_destinations as $destination ) {
                $str_return .= '<li>' . $destination->origin . '</li>';
            }
            $str_return .= '</ul></div>';
        } else {
            $str_return .= 'No existe informaci&oacute;n de destinos para este municipio';
        }

        return $str_return;
    }

    /**
     * Returns additional info for a given route
     * @return string
     */
    private function get_route_info() {
        $str_return = '';
        $code = \BUSaragon\common\controller::get( 'code' );
        $array_criteria[] = [ 'line_id', 'eq', $code, 'string' ];
        $array_criteria[] = [ 'active', 'eq', true, 'bool' ];
        $obj_route_secuence = new modelroutesbusstopsecuence();
        $array_secuence = $obj_route_secuence->get_all( $array_criteria );

        $obj_route = new modelroutes();
        $array_criteria_route[] = [ 'code', 'eq', $code, 'string' ];
        $array_routes = $obj_route->get_all( $array_criteria_route );
        if ( !empty( $array_routes ) ) {
            $obj_route = reset( $array_routes );
            $str_return .= '<h4>' . $obj_route->name . '</h4>';
        }
        if ( !empty( $array_secuence ) ) {
            $str_return .= '<div style="max-height: 500px;overflow-y:auto; padding:10px;"><ul>';
            foreach ( $array_secuence as $obj_secuence ) {
                $str_return .= '<li>' . $obj_secuence->name . '</li>';
            }
            $str_return .= '</ul></div>';
        } else {
            $str_return .= 'No existe informaci&oacute;n asociada a esta ruta.';
        }

        return $str_return;
    }
}
