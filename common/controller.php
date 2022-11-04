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
 * Basic actions controller for the app
 * @author ITERNOVA (info@iternova.net)
 * @version 1.0.0 - 20221001
 * @package common
 * @copyright 2022 ITERNOVA SL
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace BUSaragon\common;

class controller {

    const ENDPOINT_BUS_STOP_ARAGON = 1;
    const ENDPOINT_BUS_STOP_CTAZ = 2;
    const ENDPOINT_BUS_STOP_REMAINING_TIMES_CTAZ = 3;
    const ENDOPOINT_BUS_VEHICLES_ARAGON = 4;
    const ENDOPOINT_BUS_VEHICLES_POSITION_ARAGON = 5;
    const ENDOPOINT_BUS_VEHICLES_POSITION_CTAZ = 6;
    const ENDOPOINT_BUS_VEHICLES_HISTORIC_ARAGON = 7;
    const ENDOPOINT_BUS_VEHICLES_DISTANCE_ARAGON = 8;
    const ENDOPOINT_BUS_ROUTES_ARAGON = 9;
    const ENDOPOINT_BUS_ROUTES_CTAZ = 10;
    const ENDOPOINT_BUS_CITIES_CTAZ = 11;
    const ENDOPOINT_BUS_CITIES_DESTINATION_CTAZ = 12;

    /**
     * Funcion para mostrar la cabecera html
     *
     * @param boolean $echo Lo muestra por pantalla si true
     * @param boolean $script Incluye scripts
     */
    public static function show_html_header( $echo = true, $script = true ) {
        $str = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<!--
			Twenty by HTML5 UP
			html5up.net | @ajlkn
			Free for personal and commercial use under the CCA 3.0 license (html5up.net/license)
		-->
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es">
		<head>
		    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
			<title>BUS Arag&oacute;n</title>
			<meta charset="utf-8" />
			<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
			<link rel="stylesheet" href="css/main.css" />
			<noscript><link rel="stylesheet" href="css/noscript.css" /></noscript>			
			<link rel="shortcut icon" href="img/favicon.ico">
        
            <!-- Scripts -->
            <script src="libs/js/jquery.min.js"></script>
            <script src="libs/js/jquery-ui/jquery-ui.js"></script>
            <!-- DATATABLES -->
            <link href="//cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css" rel="stylesheet">
            <script src="libs/js/jquery.dataTables.min.js"></script>
		</head>';

        if ( $echo ) {
            echo $str;
        }

        return $str;
    }

    /**
     * Funcion para mostrar el pie html
     */
    public static function show_html_footer( $echo = true ) {
        $str = '<!-- Footer -->
                        <footer id="footer">
                            <ul class="icons">
                                <li><a href="https://twitter.com/tecnocarreteras" target="_blank" class="icon brands circle fa-twitter"><span class="label">Twitter</span></a></li>
                                <li><a href="https://facebook.com/tecnocarreteras" target="_blank" class="icon brands circle fa-facebook-f"><span class="label">Facebook</span></a></li>
                                <li><a href="https://github.com/soporteiternova/busaragon" target="_blank" class="icon brands circle fa-github"><span class="label">Github</span></a></li>
                            </ul>
                            <ul class="copyright">
                                <li>Aplicaci&oacute;n subvencionada por el Gobierno de Arag&oacute;n - &copy; ' . date( 'Y' ) . ' <a href="https://www.iternova.net/" target="_blank">ITERNOVA</a></li>
                            </ul>
                        </footer>
                </div>
            <script src="libs/js/jquery.dropotron.min.js"></script>
            <script src="libs/js/jquery.scrolly.min.js"></script>
            <script src="libs/js/jquery.scrollgress.min.js"></script>
            <script src="libs/js/jquery.scrollex.min.js"></script>
            <script src="libs/js/browser.min.js"></script>
            <script src="libs/js/breakpoints.min.js"></script>
            <script src="libs/js/util.js"></script>
            <script src="libs/js/main.js"></script>
        
            </body>
        </html>';

        if ( $echo ) {
            echo $str;
        }
        return $str;
    }

    /**
     * Funcion para mostrar el cuerpo de la pagina
     */
    public static function show_html_body() {
        $zone = self::get( 'zone' );
        $class_start = '';
        $class_map = '';
        $class_routes = '';

        switch ( $zone ) {
            case'bus_stop':
            case 'vehicles':
                $class_map = 'current';
                break;
            case 'routes':
                $class_routes = 'current';
                break;
            default:
                $class_start = 'current';
        }
        $str = '<body class="no-sidebar is-preload">
            <div id="page-wrapper">
    
                <!-- Header -->
                <header id="header">
                    <h1 id="logo"><a href="index.php">BUS <span>Arag&oacute;n</span></a></h1>
                    <nav id="nav">
                        <ul>
                            <li class="' . $class_start . '"><a href="index.php">Inicio</a></li>
                            <li class="submenu ' . $class_map . '">
                                <a href="#">Visualizaci&oacute;n sobre mapa</a>
                                <ul>
                                    <li><a href="?&amp;zone=bus_stop&amp;action=listing">Paradas</a></li>
                                    <li><a href="?&amp;zone=vehicles&amp;action=listing">Veh&iacute;culos</a></li>
                                </ul>
                            </li>
                            <li class="submenu ' . $class_routes . '">
                                <a href="#">Informaci&oacute;n sobre veh&iacute;culos</a>
                                <ul>
                                    <li><a href="?&amp;zone=routes&amp;action=routes_listing">Listado de rutas</a></li>
                                    <li><a href="?&amp;zone=routes&amp;action=tab_listing">B&uacute;squeda de paradas</a></li>
                                    <li><a href="?&amp;zone=routes&amp;action=cities_listing">B&uacute;squeda municipios</a></li>
                                </ul>
                            </li>
                        </ul>
                    </nav>
                </header>
                
                <!-- Main -->
                <article id="main">

                    <header class="special container">
                        <span class="icon solid fa-mobile-alt"></span>
                        <h2>BUS <strong>Arag&oacute;n </strong ></h2 >
                        <p>Toda la informac&oacute;n del transporte p&uacute;blico interurbano de viajeros por carretera en Arag&oacute;n a un click.</p>
                    </header >

                    <!--One -->
                    <section class="wrapper style4 container">

                        <!--Content -->
                            <div class="content" >';

        switch ( controller::get( 'zone' ) ) {
            case 'bus_stop':
            case 'routes':
                $controller = new \BUSaragon\busstop\controller();
                $str .= $controller->actions();
                break;
            case 'vehicles':
                $controller = new \BUSaragon\vehicles\controller();
                $str .= $controller->actions();
            default:
        }
        $str .= '        </div >

                    </section >
                </article>';

        echo $str;
    }

    /**
     * Funcion para obtener datos de $_GET
     *
     * @param String $key Clave que queremos obtener
     */
    public static function get( $key ) {
        $return = '';
        if ( isset( $_GET[ $key ] ) ) {
            $return = trim( $_GET[ $key ] );
        }
        return $return;
    }

    /**
     * Funcion para obtener datos de $_POST
     */
    public static function post( $key ) {
        $return = '';
        if ( isset( $_POST[ $key ] ) ) {
            $return = trim( $_POST[ $key ] );
        }
        return $return;
    }

    /**
     * Proporciona la api key de google asociada al dominio
     */
    public static function google_key() {
        return file_get_contents( __DIR__ . '/../config/googlemaps.key' ); // Local
    }

    public static function get_endpoint_url( $endpoint ) {
        $url = '';
        switch ( $endpoint ) {
            case self::ENDPOINT_BUS_STOP_ARAGON:
                $url = 'https://opendata.aragon.es/GA_OD_Core/download?view_id=150&formato=json';
                break;
            case self::ENDPOINT_BUS_STOP_CTAZ:
                $url = 'https://opendata.aragon.es/GA_OD_Core/download?resource_id=2187&formato=json';
                break;
            case self::ENDPOINT_BUS_STOP_REMAINING_TIMES_CTAZ:
                $url = 'https://opendata.aragon.es/GA_OD_Core/download?resource_id=2190&formato=json';
                break;
            case self::ENDOPOINT_BUS_VEHICLES_ARAGON:
                $url = 'https://opendata.aragon.es/GA_OD_Core/download?resource_id=2181&formato=json';
                break;
            case self::ENDOPOINT_BUS_VEHICLES_POSITION_ARAGON:
                $url = 'https://opendata.aragon.es/GA_OD_Core/download?resource_id=2183&formato=json';
                break;
            case self::ENDOPOINT_BUS_VEHICLES_POSITION_CTAZ:
                $url = 'https://opendata.aragon.es/GA_OD_Core/download?resource_id=2196&formato=json';
                break;
            case self::ENDOPOINT_BUS_VEHICLES_HISTORIC_ARAGON:
                $url = 'https://opendata.aragon.es/GA_OD_Core/download?resource_id=2184&formato=json';
                break;
            case self::ENDOPOINT_BUS_VEHICLES_DISTANCE_ARAGON:
                $url = 'https://opendata.aragon.es/GA_OD_Core/download?resource_id=2185&formato=json';
                break;
            case self::ENDOPOINT_BUS_ROUTES_ARAGON:
                $url = 'https://opendata.aragon.es/GA_OD_Core/download?view_id=151&formato=json';
                break;
            case self::ENDOPOINT_BUS_ROUTES_CTAZ:
                $url = 'https://opendata.aragon.es/GA_OD_Core/download?resource_id=2188&formato=json';
                break;
            case self::ENDOPOINT_BUS_CITIES_CTAZ:
                $url = 'https://opendata.aragon.es/GA_OD_Core/download?resource_id=2192&formato=json';
                break;
            case self::ENDOPOINT_BUS_CITIES_DESTINATION_CTAZ:
                $url = 'https://opendata.aragon.es/GA_OD_Core/download?resource_id=2193&formato=json';
                break;
        }
        return $url;
    }

    /**
     * Executes cron functions to load data from OpenData API
     * @return bool
     */
    public function crondaemon() {
        $controller = new \BUSaragon\busstop\controller();
        $ret = $controller->actions( 'crondaemon' );

        $controller = new \BUSaragon\vehicles\controller();
        $ret &= $controller->actions( 'crondaemon' );

        return $ret;
    }
}
