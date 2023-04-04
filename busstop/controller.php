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
            case 'get_busstop_destinations':
                return $this->get_busstop_destinations();
                break;
            case 'get_route_time':
                return $this->get_route_time();
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
        $hour = (int) date( 'H' );
        if ( $hour === 0 && $minute === 15 ) {
            $array_endpoints = [ \BUSaragon\common\controller::ENDPOINT_BUS_STOP_ARAGON, \BUSaragon\common\controller::ENDPOINT_BUS_STOP_CTAZ ];

            foreach ( $array_endpoints as $endpoint ) {
                $api_url = \BUSaragon\common\controller::get_endpoint_url( $endpoint );
                $array_objs = json_decode( file_get_contents( $api_url ) );

                if ( !empty( $array_objs ) ) {
                    foreach ( $array_objs as $obj ) {
                        $bus_stop_obj = new model();
                        $bus_stop_obj->update_from_api( $obj, $endpoint );
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

        if ( $minute === 0 ) {
            // Busstop secuence for routes
            $api_url = \BUSaragon\common\controller::get_endpoint_url( \BUSaragon\common\controller::ENDOPOINT_BUS_ROUTES_BUSSTOP_SECUENCE_CTAZ );
            $array_objs = json_decode( file_get_contents( $api_url ) );

            if ( !empty( $array_objs ) ) {
                foreach ( $array_objs as $obj ) {
                    $bus_stop_secuence_obj = new modelroutesbusstopsecuence();
                    $bus_stop_secuence_obj->update_from_api( $obj, \BUSaragon\common\controller::ENDOPOINT_BUS_ROUTES_BUSSTOP_SECUENCE_CTAZ );
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
        return '<h2>Listado de paradas de autob&uacute;s</h2>Puede acceder a los horarios de cada ruta de cada una de las paradas pulsando en el icono de la misma. La aplicaci&oacute;n le localizar&aacute; de forma autom&aacute;tica en su posici&oacute;n actual.<br/><br/>' . \BUSaragon\common\map::create_map( $array_markers, 100, 800, true, true );
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
        $url_bus_partial = \BUSaragon\common\utils::get_server_url() . '/?zone=routes&action=get_busstop_destinations&code=';
        foreach ( $array_obj_busstop as $obj_busstop ) {
            $url_buse_info = $url_bus_partial . $obj_busstop->code;
            $str_address = $obj_busstop->network === \BUSaragon\common\controller::ENDPOINT_BUS_STOP_CTAZ ? '<a href="javascript:void(0);" onclick="$(\'#busstop_info_dialog\').dialog(\'open\');$(\'#busstop_info_dialog\').load(\'' . $url_buse_info . '\');">' . $obj_busstop->address . '</a>' : $obj_busstop->address;
            $array_data[] = [ 'city' => $obj_busstop->city, 'address' => $str_address ];
        }

        $str_return = '<h2>Listado de paradas</h2>Listado de paradas de la red del Gobierno de Arag&oacute;n y del CTAZ. En el caso de las paradas correspondientes al CTAZ, puede acceder a posibles destinos desde dicha parada pulsando sobre la direcci&oacute;n de la misma.<br/><br/>';
        $str_return .= \Jupitern\Table\Table::instance()
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
        $str_return .= '<div id="busstop_info_dialog" style="" title="Posibles destinos"></div>';
        $str_return .= "<script type=\"text/javascript\">
                            \$(document).ready( function () {
                                \$('#busstop_table').DataTable();
                            \$('#busstop_info_dialog').dialog();
                            \$('#busstop_info_dialog').dialog('close');
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
        $url_routes_time_partial = \BUSaragon\common\utils::get_server_url() . '/?zone=routes&action=get_route_time&code=';
        foreach ( $array_obj_routes as $obj_route ) {
            $url_route_info = $url_routes_info_partial . $obj_route->code;
            $url_route_time = $url_routes_time_partial . $obj_route->code;
            $str_name = $obj_route->network === \BUSaragon\common\controller::ENDOPOINT_BUS_ROUTES_CTAZ ? '<a href="javascript:void(0);" onclick="$(\'#route_info_dialog\').dialog(\'open\');$(\'#route_info_dialog\').load(\'' . $url_route_info . '\');">' . $obj_route->name . '</a>' : $obj_route->name;
            if ( $obj_route->network === \BUSaragon\common\controller::ENDOPOINT_BUS_ROUTES_CTAZ ) {
                $str_actions = '<a href="javascript:void(0);" onclick="$(\'#route_time_dialog\').dialog(\'open\');$(\'#route_time_dialog\').load(\'' . $url_route_time . '\');"><img src="img/clock.png" alt="Tiempos de ruta" title="Tiempos de ruta" width="10%"/>';
            } else {
                $str_actions = '';
            }
            $array_data[] = [ 'name' => $str_name, 'origin' => $obj_route->origin, 'destination' => $obj_route->destination, 'network' => $array_network[ (int) $obj_route->network ], 'actions' => $str_actions ];
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
                                            ->column()
                                            ->title( 'Accciones' )
                                            ->value( 'actions' )
                                            ->add()
                                            ->render( true );
        $str_return .= '<div id="route_info_dialog" style="" title="Informaci&oacute;n de la ruta"></div>';
        $str_return .= '<div id="route_time_dialog" style="" title="Tiempos de la ruta"></div>';
        $str_return .= "<script type=\"text/javascript\">
                            \$(document).ready( function () {
                                \$('#routes_table').DataTable();
                            \$('#route_info_dialog').dialog();
                            \$('#route_info_dialog').dialog('close');
                            \$('#route_time_dialog').dialog();
                            \$('#route_time_dialog').dialog('close');
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

        $array_criteria_cities[] = [ 'destination', 'eq', $obj_city->origin, 'string' ];
        $array_criteria_cities[] = [ 'active', 'eq', true, 'bool' ];
        $array_criteria_cities[] = [ 'network', 'eq', \BUSaragon\common\controller::ENDOPOINT_BUS_ROUTES_CTAZ, 'int' ];

        $obj_route = new modelroutes();
        $array_destinations = $obj_route->get_all( $array_criteria_cities );
        if ( empty( $array_destinations ) ) {
            $array_criteria_cities = [];
            $array_criteria_cities[] = [ 'code', 'in', $array_destinations_id, 'int' ];
            $array_criteria_cities[] = [ 'active', 'eq', true, 'bool' ];
            $array_criteria_cities[] = [ 'network', 'eq', $obj_city->network, 'int' ];
            $array_destinations = $obj_city->get_all( $array_criteria_cities );
        }

        // Adding some from Aragon network
        $array_criteria_cities = [];
        $array_criteria_cities[] = [ 'destination', 'blike', $obj_city->origin, 'string' ];
        $array_criteria_cities[] = [ 'active', 'eq', true, 'bool' ];
        $array_criteria_cities[] = [ 'network', 'eq', \BUSaragon\common\controller::ENDOPOINT_BUS_ROUTES_ARAGON, 'int' ];
        $array_destinations_aragon = $obj_route->get_all( $array_criteria_cities );

        if ( !empty( $array_destinations_aragon ) ) {
            $array_destinations = array_merge( $array_destinations, $array_destinations_aragon );
        }

        if ( !empty( $array_destinations ) ) {
            $array_to_order = $array_destinations;
            $array_destinations = [];
            foreach ( $array_to_order as $destination ) {
                if ( strtolower( $destination->origin ) !== strtolower( $obj_city->origin ) ) {
                    $array_destinations[] = mb_strtoupper( $destination->origin );
                }
            }
            sort( $array_destinations );
            $array_destinations = array_unique( $array_destinations );
            $str_return .= '<div style="max-height: 500px;overflow-y:auto; padding:10px;"><ul>';
            $str_return .= '<ul><li>' . implode( '</li><li>', $array_destinations ) . '</li></ul>';
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

    /**
     * Returns possible destinations for a given bus stop
     * @return string
     */
    private function get_busstop_destinations() {
        $str_return = '';
        $code = \BUSaragon\common\controller::get( 'code' );
        $array_criteria[] = [ 'code', 'eq', $code, 'string' ];
        $array_criteria[] = [ 'active', 'eq', true, 'bool' ];
        $array_criteria[] = [ 'network', 'eq', \BUSaragon\common\controller::ENDPOINT_BUS_STOP_CTAZ, 'int' ];
        $obj_busstop = new model();
        $array_busstop = $obj_busstop->get_all( $array_criteria );
        if ( !empty( $array_busstop ) ) {
            $obj_busstop = reset( $array_busstop );
            $str_return .= '<h4>' . $obj_busstop->city . ' - ' . $obj_busstop->address . '</h4>';
        }

        $obj_time = new remainingtimemodel();
        $array_criteria_time[] = [ 'code', 'eq', $obj_busstop->code, 'string' ];
        $array_time = $obj_time->get_all( $array_criteria_time );

        $array_routes = [];
        if ( !empty( $array_time ) ) {
            foreach ( $array_time as $obj_time ) {
                foreach ( $obj_time->routes as $line => $routes ) {
                    foreach ( $routes as $route_id => $route ) {
                        $array_routes[] = $route_id;
                    }
                }
            }
        }

        $obj_route = new modelroutes();
        $array_cirteria_routes[] = [ 'code', 'in', $array_routes, 'string' ];
        $array_routes = $obj_route->get_all( $array_cirteria_routes );
        if ( !empty( $array_routes ) ) {
            $str_return .= '<div style="max-height: 500px;overflow-y:auto; padding:10px;"><ul>';
            $array_sorted = [];
            foreach ( $array_routes as $obj_route ) {
                $array_sorted[] = strtoupper( $obj_route->destination );
            }
            $array_sorted = array_unique( $array_sorted );
            sort( $array_sorted );
            $str_return .= '<li>' . implode( '</li><li>', $array_sorted ) . '</li></ul></div>';
        } else {
            $str_return .= 'No existe informaci&oacute;n asociada a esta parada.';
        }

        return $str_return;
    }

    /**
     * Returns times for a route
     * @return string
     */
    private function get_route_time() {
        $str_return = '<div style="max-height:500px;overflow-y:scroll;padding:10px;">';
        $code = \BUSaragon\common\controller::get( 'code' );
        $array_criteria[] = [ 'code', 'eq', $code, 'string' ];
        $array_criteria[] = [ 'active', 'eq', true, 'bool' ];
        $array_criteria[] = [ 'network', 'eq', \BUSaragon\common\controller::ENDOPOINT_BUS_ROUTES_CTAZ, 'int' ];
        $obj_route = new modelroutes();
        $array_routes = $obj_route->get_all( $array_criteria );

        if ( !empty( $array_routes ) ) {
            $obj_route = reset( $array_routes );
            $str_return .= '<h4>' . $obj_route->name . '</h4>';
        }

        $obj_time = new remainingtimemodel();
        $array_criteria_time[] = [ 'routes_id', 'eq', $code, 'string' ];
        $array_time = $obj_time->get_all( $array_criteria_time );

        if ( !empty( $array_time ) ) {
            $obj_busstop = new model();
            $array_criteria_stop[] = [ 'network', 'eq', \BUSaragon\common\controller::ENDPOINT_BUS_STOP_CTAZ, 'int' ];

            $str_line = false;
            $str_other_lines = false;
            foreach ( $array_time as $obj_time ) {
                foreach ( $obj_time->times as $line => $routes ) {
                    if ( array_key_exists( $code, $obj_time->times[ $line ] ) ) {
                        $array_criteria_stop[ 1 ] = [ 'code', 'eq', $obj_time->code, 'string' ];
                        $array_busstop = $obj_busstop->get_all( $array_criteria_stop );
                        if ( !$str_line ) {
                            $str_return .= 'Ruta perteneciente a la l&iacute;nea ' . $line . ': ' . $obj_time->lines[ $line ] . '<br/><br/>';
                            $array_criteria_routes[] = [ 'lines_id', 'eq', $line, 'string' ];
                            $array_times_routes = $obj_time->get_all( $array_criteria_routes );
                            $other_routes = [];
                            foreach ( $array_times_routes as $time_route ) {
                                if ( isset( $time_route->routes[ $line ] ) ) {
                                    $other_routes = array_merge( $other_routes, $time_route->routes[ $line ] );
                                }
                            }
                            $other_routes = array_unique( $other_routes );
                            $str_return .= 'Rutas relacionadas:<ul><li>' . implode( '</li><li>', $other_routes ) . '</li></ul>';
                            $str_line = true;
                        }
                        if ( !empty( $array_busstop ) ) {
                            $bus_stop = reset( $array_busstop );
                            $str_return .= '<h4>' . $bus_stop->address . '</h4><div style="max-height: 100px;overflow-y:scroll"><ul><li>' . implode( '</li><li>', $obj_time->times[ $line ][ $code ] ) . '</li></ul></div>';
                        }
                    }
                }
            }
        }

        return $str_return . '</div>';
    }
}
