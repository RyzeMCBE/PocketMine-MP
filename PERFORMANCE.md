# Rendimiento y validación

El objetivo de 100–150 jugadores necesita una prueba con clientes activos, plugins, mapas y hardware representativos. Las pruebas del núcleo no certifican esa capacidad.

## Comportamiento

- La cola de bloques conserva un vencimiento absoluto por posición. Las entradas sustituidas no ejecutan ni vuelven a programar el bloque.
- Los portales automáticos buscan progresivamente y comparten consultas al mismo destino. Se revalidan el origen, la entidad, el plazo y el portal de llegada. Un índice con hasta 2048 chunks por mundo conserva candidatos y se invalida con cambios de bloques, reemplazos y descargas de chunks. Los destinos en caché exigen una cobertura completa vigente.
- El autoguardado reparte jugadores, chunks y metadatos entre ticks y mundos. Una escritura fallida mantiene su tarea para reintento, con pausa de un segundo entre errores. Los chunks se consultan cuando se ejecuta cada tarea; no se escribe una referencia antigua después de reemplazar o descargar terreno.
- `save()`, `saveChunks()`, `/save-all` y la descarga/cierre de mundos mantienen el guardado síncrono. El autoguardado progresivo no es una transacción global entre todos los mundos, inventarios y jugadores; el cierre normal sigue guardando su estado actual completo.
- El modo automático de trabajadores considera afinidad y cuotas/cpusets cgroup v1/v2 en las ubicaciones Linux habituales, incluyendo ancestros del grupo. La cuota fraccionaria se redondea hacia arriba a unidades de CPU lógicas. Un número explícito de trabajadores conserva prioridad sobre la detección.
- La generación del núcleo usa un subconjunto del pool y limita nuevas admisiones según su cola compartida. Los mundos con solicitudes pendientes se atienden por turnos, tanto al completar generación como en el tick. No se crea un segundo pool ni más hilos por jugador.
- Las tolvas alimentadas dejan de sondear periódicamente y despiertan por notificaciones vecinas. Las comprobaciones de inventario evitan clones innecesarios y descartan rutas imposibles. Las tolvas sin alimentación conservan el intervalo de ocho ticks, incluida la recogida de objetos.
- LevelDB guarda referencias positivas a claves reales de dimensiones y comprueba su existencia antes de reutilizarlas. No almacena la ausencia de una dimensión: importar otra dimensión después no queda oculto por una caché negativa.

## Valores iniciales

En `pocketmine.yml`:

```yaml
settings:
  async-workers: auto
network:
  compression-level: 6
  async-compression: true
  async-compression-threshold: 10000
chunk-generation:
  population-queue-size: 32
  worker-limit: 0
  max-pending-tasks: 0
autosave:
  max-milliseconds-per-tick: 2
  max-tasks-per-tick: 8
```

`worker-limit: 0` permite usar para generación hasta el tamaño del pool menos uno, con mínimo uno. Así queda otro trabajador disponible para preparación/compresión cuando el pool tiene varios. Los plugins que envían tareas al pool general también utilizan ese pool y deben evitar I/O bloqueante.

`max-pending-tasks: 0` aplica un umbral de admisión equivalente a dos veces el número de trabajadores. Es un umbral cooperativo; los trabajos de registro y otros productores también pueden añadir tareas. No descarta solicitudes de jugadores.

Los límites de autoguardado se comprueban entre operaciones indivisibles: una escritura o un evento de plugin puede superar el presupuesto. La generación síncrona y la integración final de resultados siguen consumiendo tiempo del hilo principal.

En `dimensions.yml`:

```yaml
portal:
  search-chunks-per-tick: 8
  search-budget-milliseconds: 2
  load-timeout-ticks: 600
```

Los dos límites de búsqueda son globales para los viajes automáticos, no se multiplican por jugador. La lectura de un chunk o su escaneo puede superar el presupuesto individual. El plazo incluye la población y el descubrimiento; salir del portal, cambiar de mundo o invalidar un enlace cancela la operación.

Las instalaciones existentes conservan sus archivos. Las propiedades nuevas tienen valores de respaldo; una opción ya presente como `network.async-compression: false` conserva su valor y debe cambiarse expresamente si se desea compararla.

## Diagnóstico

`/status` añade:

- media, p95, p99 y máximo del tiempo efectivo del hilo principal, incluyendo notificaciones, sobre los últimos 1200 ticks;
- capacidad CPU estimada, límite de trabajadores y número de tareas pendientes;
- p95 de espera, ejecución y callback sobre las últimas 1200 tareas completadas;
- trabajo de autoguardado pendiente, tareas completadas y coste p95/máximo de sus pasos.

Los historiales están acotados. Los percentiles se ordenan solo al consultar el diagnóstico. Los datos de espera y ejecución se toman con reloj monotónico en los trabajadores. La espera mide hasta el inicio de `onRun()`, no el tiempo de retorno del callback.

El soporte de `libdeflate` ya está integrado en el compresor y se usa cuando existe `libdeflate_deflate_compress()`. Conviene comprobar el binario desplegado. Mantener PHP ZTS y sus extensiones compatibles; OPcache y el PHAR de producción ayudan a evitar carga repetida. El nivel 6 se conserva; comparar 1–3 requiere medir también el ancho de banda. JIT necesita una comparación aislada de corrección y estabilidad.

## Prueba de capacidad

1. Fijar build, configuración, plugins, CPU/cuota, memoria, almacenamiento y mapas. Comprobar conexión y transiciones con un cliente Bedrock 1.26.52.
2. Medir servidor vacío, arranque frío y cachés calientes.
3. Subir a 25, 50, 75, 100, 125 y 150 clientes activos. Cubrir varios autoguardados por etapa.
4. Repetir exploración distribuida, PvP concentrado, inventarios/economía, objetos/tolvas, entradas simultáneas y viajes entre dimensiones.
5. Comparar un ajuste por vez. Registrar el tiempo del hilo principal, espera del pool, crecimiento de colas, memoria del proceso/contenedor y tráfico/retransmisión.
6. Tras elegir valores, sostener la carga al menos dos horas y verificar guardado y reinicio.

Como objetivos iniciales: 20 TPS sostenidos, p95 bajo 40 ms, p99 bajo 50 ms y colas sin crecimiento continuo. Son criterios de evaluación, no resultados ya obtenidos con 150 jugadores.

## Comprobaciones del código

Con PHP y extensiones compatibles, ejecutar:

```sh
composer install --prefer-dist
php vendor/bin/phpunit --bootstrap vendor/autoload.php --fail-on-warning tests/phpunit
php vendor/bin/phpstan analyze --no-progress --memory-limit=2G
composer update-codegen
```

Las pruebas cubren sustituciones de actualizaciones, cachés y búsqueda incremental de portales, cancelación de viajes, afinidad/cuotas CPU, presupuestos y reintento de guardados, referencias LevelDB obsoletas, reparto de generación entre mundos y conservación del comportamiento de las tolvas. La compatibilidad de protocolo permanece fijada por `composer.lock`.
