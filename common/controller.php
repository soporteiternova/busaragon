<?php
/**
 * BUSaragon - ITERNOVA <info@iternova.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

 /**
  * Basic actions controller for the app
  *
 * @author ITERNOVA (info@iternova.net)
 * @version 1.0.0 - 20221001
 * @package common
 * @copyright 2022 ITERNOVA SL
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace BUSaragon\common;

class controller {
	/**
	 * Funcion para mostrar la cabecera html
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
		</head>';

		if($echo) echo $str;

        return $str;
	}

	/**
	 * Funcion para mostrar el pie html
	 */
	public static function show_html_footer($echo = true) {
        $str = '<!-- Footer -->
                        <footer id="footer">
                            <ul class="icons">
                                <li><a href="https://twitter.com/tecnocarreteras" target="_blank" class="icon brands circle fa-twitter"><span class="label">Twitter</span></a></li>
                                <li><a href="https://facebook.com/tecnocarreteras" target="_blank" class="icon brands circle fa-facebook-f"><span class="label">Facebook</span></a></li>
                                <li><a href="https://github.com/soporteiternova/busaragon" target="_blank" class="icon brands circle fa-github"><span class="label">Github</span></a></li>
                            </ul>
                            <ul class="copyright">
                                <li>Aplicaci&oacute;n subvencionada por el Gobierno de Arag&oacute;n - &copy; ' . date('Y') . ' <a href="https://www.iternova.net/" target="_blank">ITERNOVA</a></li>
                            </ul>
                        </footer>
                </div>
        
                <!-- Scripts -->
                    <script src="libs/js/jquery.min.js"></script>
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

		if( $echo) echo $str;
		return $str;
	}

	/**
	 * Funcion para mostrar el cuerpo de la pagina
	 */
	public static function show_html_body() {
		$str = '<body class="no-sidebar is-preload">
            <div id="page-wrapper">
    
                <!-- Header -->
                <header id="header">
                    <h1 id="logo"><a href="index.html">BUS <span>Arag&oacute;n</span></a></h1>
                    <nav id="nav">
                        <ul>
                            <li class="current"><a href="index.html">Inicio</a></li>
                            <li class="submenu">
                                <a href="#">Visualizaci&oacute;n sobre mapa</a>
                                <ul>
                                    <li><a href="left-sidebar.html">Paradas</a></li>
                                    <li><a href="right-sidebar.html">Veh&iacute;culos</a></li>
                                </ul>
                            </li>
                            <li class="submenu">
                                <a href="#">Informaci&oacute;n sobre veh&iacute;culos</a>
                                <ul>
                                    <li><a href="left-sidebar.html">Listado de rutas</a></li>
                                    <li><a href="right-sidebar.html">B&uacute;squeda de paradas</a></li>
                                    <li><a href="right-sidebar.html">B&uacute;squeda municipios</a></li>
                                </ul>
                            </li>
                            <li><a href="#" class="button primary">Sign Up</a></li>
                        </ul>
                    </nav>
                </header>
                
                <!-- Main -->
                <article id="main">

                    <header class="special container">
                        <span class="icon solid fa-mobile-alt"></span>
                        <h2>Titulo <strong>No Sidebar </strong ></h2 >
                        <p > Where that in the center faces the nameless horrors alone .</p >
                    </header >

                    <!--One -->
                    <section class="wrapper style4 container" >

                        <!--Content -->
                            <div class="content" >
                                <section >
                                    <a href = "#" class="image featured" ><img src = "img/pic04.jpg" alt = "" /></a >
                                    <header >
                                        <h3 > Dolore Amet Consequat </h3 >
                                    </header >
                                    <p > Aliquam massa urna, imperdiet sit amet mi non, bibendum euismod est . Curabitur mi justo, tincidunt vel eros ullamcorper, porta cursus justo . Cras vel neque eros . Vestibulum diam quam, mollis at consectetur non, malesuada quis augue . Morbi tincidunt pretium interdum . Morbi mattis elementum orci, nec dictum massa . Morbi eu faucibus massa . Aliquam massa urna, imperdiet sit amet mi non, bibendum euismod est . Curabitur mi justo, tincidunt vel eros ullamcorper, porta cursus justo . Cras vel neque eros . Vestibulum diam .</p >
                                    <p > Vestibulum diam quam, mollis at consectetur non, malesuada quis augue . Morbi tincidunt pretium interdum . Morbi mattis elementum orci, nec dictum porta cursus justo . Quisque ultricies lorem in ligula condimentum, et egestas turpis sagittis . Cras ac nunc urna . Nullam eget lobortis purus . Phasellus vitae tortor non est placerat tristique . Sed id sem et massa ornare pellentesque . Maecenas pharetra porta accumsan . </p >
                                    <p > In vestibulum massa quis arcu lobortis tempus . Nam pretium arcu in odio vulputate luctus . Suspendisse euismod lorem eget lacinia fringilla . Sed sed felis justo . Nunc sodales elit in laoreet aliquam . Nam gravida, nisl sit amet iaculis porttitor, risus nisi rutrum metus, non hendrerit ipsum arcu tristique est .</p >
                                </section >
                            </div >

                    </section >
                </article>';

        echo $str;
	}

	/**
	 * Funcion para obtener datos de $_GET
	 * @param String $key Clave que queremos obtener
	 */
	 public static function get( $key ) {
	 	$return = '';
	 	if ( isset( $_GET[$key] ) ) {
	 		$return = trim( $_GET[$key] );
	 	}
	 	return $return;
	 }

	 /**
	  * Funcion para obtener datos de $_POST
	  */
	 public static function post( $key ) {
	 	$return = '';
	 	if ( isset( $_POST[$key] ) ) {
	 		$return = trim( $_POST[$key] );
	 	}
	 	return $return;
	 }

	 /**
	  * Proporciona la api key de google asociada al dominio
	  */
	 public static function google_key() {
			return file_get_contents(__DIR__.'/../maps/googlemaps.key'); // Local
	 }
}
