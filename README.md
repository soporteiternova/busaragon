# BUS Aragón

Aplicación para el acceso a toda la información disponible en OpenData del Gobierno de Aragón, referente a los autobuses de las líneas que depnden de esta institución.

**Aplicación subvencionada por el Gobierno de Aragón**

## Crondaemon

Se debe configurar la siguiente orden en crontab para poder cargar los datos de la aplicación de forma automática:

* `* * * * * php path_to_prorject/common/crondaemon.php`

Donde path_to_prorject será el directorio de instalación.
