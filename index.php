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
 * Acces file to the app
 * @author ITERNOVA (info@iternova.net)
 * @version 1.0.0 - 20221001
 * @copyright 2022 ITERNOVA SL
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */

declare( strict_types=0 );

// Includes
require __DIR__ . '/libs/composer/vendor/autoload.php';

session_start();

switch ( \BUSaragon\common\controller::get( 'action' ) ) {
	case 'incidents_cron':
		// Obtiene los cortes de agua
		\BUSaragon\incidents\controller::load_xml();
		break;
	case 'incidents_notify':
		// Notifica los cortes de agua
		\BUSaragon\incidents\controller::notify();
	case 'login':
		\BUSaragon\users\controller::login();
		break;
	case 'suggest':
		\BUSaragon\incidents\controller::suggest();
		break;
	case 'estadisticas':
	case 'cortes':
	default:
		// Index
		\BUSaragon\common\controller::show_html_header();
		\BUSaragon\common\controller::show_html_body();
		\BUSaragon\common\controller::show_html_footer();
		break;
}
