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
 * Map generation function
 * @author ITERNOVA (info@iternova.net)
 * @version 1.0.0 - 20221001
 * @package common
 * @copyright 2022 ITERNOVA SL
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace BUSaragon\common;

class map {

    /**
     * Returns google api key stored in config file
     * @return string
     */
    private static function google_key() {
        return trim( file_get_contents( __DIR__ . '/../config/googlemaps.key' ) );
    }

    /**
     * Generates a map with given markers
     *
     * @param array $array_markers marker array to be represented in map
     * @param int $sizex Ancho del mapa
     * @param int $sizey Alto del mapa
     *
     * @return string
     */
    public static function create_map( $array_markers, $sizex = 600, $sizey = 400, $set_center_user = false ) {
        $str = '';

        // JS googlemaps
        $str .= '<script type="text/javascript" src="https://maps.google.com/maps/api/js?key=' . self::google_key() . '"></script>';
        $rand = rand();
        // Generamos el mapa
        $str .= "<script type=\"text/javascript\">
                var map{$rand}=null;
 				function initialize(){
                    const centerPoint = {lat: 41.65, lng: -0.87};
 					map{$rand} = new google.maps.Map(document.getElementById('incidents_map$rand'),{
                                                        zoom:12,
                                                        center: centerPoint,
                                                    });";

        if ( is_array( $array_markers ) ) {
            foreach ( $array_markers as $marker ) {
                $marker[ 'title' ] = htmlspecialchars( $marker[ 'title' ] );
                $marker[ 'title' ] = str_replace( "'", "\'", $marker[ 'title' ] );

                $str .= " 					var marker_" . $marker[ 'id' ] . " = new google.maps.Marker({
                                                                        position:{lat:" . $marker[ 'lat' ] . ",lng:" . $marker[ 'lng' ] . "},
                                                                        title:'TEST',
                                                                        icon: {
                                                                          url: 'http://maps.google.com/mapfiles/ms/icons/" . $marker[ 'marker_color' ] . "-dot.png'
                                                                        },
                                                                    });
                                             marker_" . $marker[ 'id' ] . ".setMap(map{$rand});
                                             infowindow_" . $marker[ 'id' ] . "= new google.maps.InfoWindow({content:'<div id=\'content\'>" . $marker[ 'title' ] . "</div>'});
                                             marker_" . $marker[ 'id' ] . ".addListener('click', () => {
                                                infowindow_" . $marker[ 'id' ] . ".open({
                                                  anchor: marker_" . $marker[ 'id' ] . ",
                                                  map{$rand},
                                                  shouldFocus: true,
                                                });
                                              });
                ";
            }
        }

        if ( $set_center_user ) {
            $str .= "if (navigator.geolocation) {
                     navigator.geolocation.getCurrentPosition(function (position) {
                         initialLocation = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                         map{$rand}.setCenter(initialLocation);
                         map{$rand}.setZoom(16);
                     });
                 }";
        }

        $str .= '}
 				$(document).ready(initialize);
 				</script>';

        $str .= '<div class="incidents_map" id="incidents_map' . $rand . '" style="height:' . $sizey . 'px;width:100%;"></div>';

        return $str;
    }
}
