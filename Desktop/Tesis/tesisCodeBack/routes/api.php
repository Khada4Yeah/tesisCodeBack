<?php

use App\Http\Controllers\CargoController;
use App\Http\Controllers\FtpArchivosController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\SolicitudCoordinadorMateriaController;
use App\Http\Controllers\MateriasHomologarController;
use App\Http\Controllers\SolicitudReComisionController;
use App\Http\Controllers\SolicitudVicedecanoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\UsuarioExternoController;
use App\Http\Middleware\ApiAuthMiddleware;
use App\Models\Materia_Homologar;
use App\Models\UsuarioExterno;
use Facade\FlareClient\Api;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware("auth:sanctum")->get("/user", function (Request $request) {
//     return $request->user();
// });

Route::post("user/prueba", [UsuarioController::class, "pruebaxd"]);

//?? RUTAS GENERALES */

//** Obtener archivo PDF del servidor FTP */
Route::get("documentos/getPDF/{filename}", [
    FtpArchivosController::class,
    "getPDF",
]);

//?? RUTAS DE LOGIN */

//* Login General
Route::post("user/login", [UsuarioController::class, "login"]);

//* Registrar Usuario Externp
Route::post("user/register", [UsuarioExternoController::class, "register"]);

//** Actualizar Datos Usuario Externo */
Route::patch("user/update", [UsuarioExternoController::class, "update"]);

//** Obtener Datos Usuario Externo */
Route::get("user/show/{idPersona}", [UsuarioExternoController::class, "show"]);

//* Decodificar Token
Route::post("user/identity", [
    UsuarioController::class,
    "decodeToken",
])->middleware(ApiAuthMiddleware::class);

//?? RUTAS DE ADMINISTRADOR ?/

//* Obtener departamentos
Route::get("admin/usuario/consultas/departamentos", [
    UsuarioController::class,
    "consultarDepartamentos",
]);

//* Obtener personal por cedula
Route::get("admin/usuario/consultas/personalCedula/{cedula}", [
    UsuarioController::class,
    "consultarPersonalCedula",
])->middleware(ApiAuthMiddleware::class);

//* Metodos comunes API
Route::resource("admin/usuario", UsuarioController::class)->middleware(
    ApiAuthMiddleware::class
);

//** Metodos comunes API
Route::resource("admin/cargo", CargoController::class)->middleware(
    ApiAuthMiddleware::class
);

//** Obtener todos los estudiantes externos */
Route::get("admin/estudiantesExternos", [
    UsuarioController::class,
    "indexEstudiantesExternos",
]);

//** Eliminar estudiante externo */
Route::delete("admin/estudiantesExternos/{idPersona}", [
    UsuarioController::class,
    "eliminarEstudianteExterno",
])->middleware(ApiAuthMiddleware::class);

//?? RUTAS SOLICITUD CAMBIO CARRERA - ESTUDIANTES ?/

//* Obtener escuelas o escuela del estudiantes
Route::get("estudiante/escuelas/{id?}", [
    SolicitudController::class,
    "obtenerEscuelas",
]);

//* Obtener malla del estudiantes
Route::get("estudiante/malla/", [SolicitudController::class, "obtenerMallas"]);

//* Obtener materias de la escuela donde quiere cambiarse
Route::get("estudiante/materias/{id}", [
    SolicitudController::class,
    "obtenerMateriasEscuelaMalla",
]);

//** Obtener todas las solicitudes del estudiante */
Route::get("estudiante/solicitudes/{idPersona}", [
    SolicitudController::class,
    "indexPorEstudiante",
]);

//** Obtener la ultima solicitud del estudiante */
Route::get("estudiante/ultimaSolicitud/{idPersona}/{tipo}", [
    SolicitudController::class,
    "obtenerUltimaSolicitud",
]);

//** Obtener la solicitud y materias para correcion del estudiante */
Route::get(
    "estudiante/solicitud/solicitudMateriasCorrecion/{idPersona}/{tipo}",
    [SolicitudController::class, "obtenerSolicitudMateriasCorrecion"]
)->middleware(ApiAuthMiddleware::class);

//** Cargar el/los PDF(s) de las correciones */
Route::post("estudiante/solicitud/cargarPdfCorrecciones", [
    SolicitudController::class,
    "cargarPdfCorreciones",
])->middleware(ApiAuthMiddleware::class);

//** Obtener las materias de la solicitud del estudiante */
Route::get("estudiante/solicitud/materiasSolicitud/{idPersona}/{tipo}", [
    SolicitudController::class,
    "obtenerMateriasSolicitud",
])->middleware(ApiAuthMiddleware::class);

//** Cargar el/los PDF de las materias a homologar */
Route::post("estudiante/solicitud/cargarPdfMaterias", [
    SolicitudController::class,
    "cargarPdfMateriasSolicitud",
]);

//* Metodos comunes API
Route::resource(
    "estudiante/solicitudes",
    SolicitudController::class
)->middleware(ApiAuthMiddleware::class);

//?? RUTAS SOLICITUD CAMBIO UNIVERSIDAD - ESTUDIANTES ?/

//** Obtener todas las solicitudes del estudiante externo */
Route::get("estudianteExterno/solicitudes/{idPersona}", [
    SolicitudController::class,
    "indexPorEstudianteExterno",
])->middleware(ApiAuthMiddleware::class);

//?? RUTAS SOLICITUD CAMBIO CARRERA - VICEDECANOS ?/

//** Obtener las solicitudes de cambio de carrera por vicedecano */
Route::get("vicedecano/solicitudes/{idPersonal}", [
    SolicitudVicedecanoController::class,
    "indexCambioCarreraPorVicedecano",
]);

//** Obtener las solicitudes de cambio de universidad por vicedecano */
Route::get("vicedecano/solicitudesExternos/{idPersonal}", [
    SolicitudVicedecanoController::class,
    "indexCambioUniversidadPorVicedecano",
]);

//* Obtener las solicitudes detalladas
Route::get("vicedecano/solicitudesDetalle/{idPersonal}/{idSolicitud}/{tipo}", [
    SolicitudVicedecanoController::class,
    "detalleSolicitud",
]);

//* Obtener el historial de materias aprobadas del estudiantes
Route::get("vicedecano/historialEstudiante/", [
    SolicitudVicedecanoController::class,
    "obtenerMateriasAprobadas",
]);

//* Obtener la escuela del vicedecano
Route::get("vicedecano/obtenerEscuelaVicedecano/{id}", [
    SolicitudVicedecanoController::class,
    "obtenerEscuelaVicedecano",
]);

//** Obtener todos los departamentos */
Route::get("vicedecano/obtenerDepartamentos", [
    SolicitudVicedecanoController::class,
    "obtenerDepartamentos",
])->middleware(ApiAuthMiddleware::class);

//** Asignar departamento a la solicitud */
Route::patch(
    "vicedecano/asignarDepartamentoSolicitud/{idSolicitud}/{idDepartamento}",
    [SolicitudVicedecanoController::class, "asignarDepartamentoSolicitud"]
)->middleware(ApiAuthMiddleware::class);

//** Enviar solicitud al Responsable de la Comision */
Route::patch("vicedecano/enviarReComision/{idSolicitud}", [
    SolicitudVicedecanoController::class,
    "enviarSolicitudReComision",
])->middleware(ApiAuthMiddleware::class);

//** Solicitar Correciones */
Route::patch("vicedecano/correcionSolicitud", [
    SolicitudVicedecanoController::class,
    "updateParaCorrecion",
])->middleware(ApiAuthMiddleware::class);

//* Rechazar Solicitud
Route::put("vicedecano/rechazarSolicitud/{id}", [
    SolicitudVicedecanoController::class,
    "updateRechazoSolicitud",
])->middleware(ApiAuthMiddleware::class);

//?? RUTAS SOLICITUD CAMBIO CARRERA - RESPONSABLE COMISION ?/

//** Obtener las solicitudes de cambio de carrera por responsable comision */
Route::get("reComision/solicitudes/{idPersonal}", [
    SolicitudReComisionController::class,
    "indexCambioCarreraPorReComision",
]);

//** Obtener las solicitudes de cambio de universidad por responsable comision */
Route::get("reComision/solicitudesExternos/{idPersonal}", [
    SolicitudReComisionController::class,
    "indexCambioUniversidadPorReComision",
]);

Route::get("user/consultarPeriodo/{id}", [
    UsuarioController::class,
    "consultarPeriodo",
]);

//** Obtener las solicitudes detalladas */
Route::get("reComision/solicitudesDetalle/{idPersonal}/{idSolicitud}/{tipo}", [
    SolicitudReComisionController::class,
    "detalleSolicitud",
]);

//** Obtener el departamento del Responsable de la Comision */
Route::get("reComision/departamentoReComision/{id}/{flag}", [
    SolicitudReComisionController::class,
    "obtenerDepartamentoReComision",
]);

//** Enviar la solicitud a los Coordinadores de Materias*/
Route::patch("reComision/enviarCoMaterias", [
    SolicitudReComisionController::class,
    "enviarSolicitudCoMaterias",
]);

//?? RUTAS SOLICITUD CAMBIO CARRERA - COORDINADOR MATERIA ?/

//** Obtener las solicitudes de cambio de carrera por coordinador de materias */
Route::get("coMaterias/solicitudes/{idPersonal}", [
    SolicitudCoordinadorMateriaController::class,
    "indexCambioCarreraPorCoMaterias",
]);

//** Obtener las solicitudes de cambio de universidad por coordinador de materias */
Route::get("coMaterias/solicitudesExternos/{idPersonal}", [
    SolicitudCoordinadorMateriaController::class,
    "indexCambioUniversidadPorCoMaterias",
]);

//** Obtener las solicitudes detalladas */
Route::get("coMaterias/solicitudesDetalle/{idPersonal}/{idSolicitud}/{tipo}", [
    SolicitudCoordinadorMateriaController::class,
    "detalleSolicitud",
]);

//** Obtener el departamento del Coordinador de Materias */
Route::get("coMaterias/departamentoCoMaterias/{id}/{flag}", [
    SolicitudCoordinadorMateriaController::class,
    "obtenerDepartamentoCoMaterias",
]);

//** Devolver la solicitud al Responsable de la Comision */
Route::patch("coMaterias/devolverReComision", [
    SolicitudCoordinadorMateriaController::class,
    "devolverReComision",
]);

//?? RUTAS SOLICITUD CAMBIO UNIVERSIDAD - ESTUDIANTES ?/

//* Obtener las universidad
Route::get("estudiante/universidades", [
    SolicitudController::class,
    "obtenerUniversidades",
]);

//?? RUTAS SOLICITUD CAMBIO CARRERA - DOCENTE/MATERIAS HOMOLOGAR ?/

//** Obtener los docente de acuerdo al departamento correspondiente */
Route::get("solicitudes/materias_homologar/obtenerDocentes/{idDepartamento}", [
    MateriasHomologarController::class,
    "obtenerDocentes",
]);

//** Asignar el docente que realizará el análsis de las materias
Route::patch("solicitudes/materias_homologar/asignarDocenteAnalisis/", [
    MateriasHomologarController::class,
    "actualizarDocenteAnalisis",
]);

//** Cerrar la asignacion y carga de archivos de docentes */
Route::patch("solicitudes/materias_homologar/cerrarAsignacionDocentes/", [
    MateriasHomologarController::class,
    "closeActualizarDocenteAnalisis",
]);

//** CREAR EL PDF DEL ANALISIS DEL DOCENTE*/
Route::get("solicitudes/materias_homologar/getPDFAnalisDocente", [
    MateriasHomologarController::class,
    "createPdfAnalisisMateria",
]);

//** Obtener las solicitudes de cambio de carrera por docente análisis */
Route::get("doAnalisis/solicitudes/{idPersonal}", [
    MateriasHomologarController::class,
    "indexCambioCarreraPorDoAnalisis",
]);

//** Obtener las solicitudes de cambio de universidad por docente análisis */
Route::get("doAnalisis/solicitudesExternos/{idPersonal}", [
    MateriasHomologarController::class,
    "indexCambioUniversidadPorDoAnalisis",
]);

//** Obtener las solicitudes detalladas */
Route::get("doAnalisis/solicitudesDetalle/{idPersonal}/{idSolicitud}/{tipo}", [
    MateriasHomologarController::class,
    "detalleSolicitud",
]);

//** Obtener la solicitud detallada con la materia en especifico para el Docente Analisis */
Route::get("doAnalisis/solicitudMateriaDetalle/", [
    MateriasHomologarController::class,
    "getSolicitudMateriaDoAnalisis",
]);

//** Actualizar el registro de la materia a homologar (analisis realizado) */
Route::patch("doAnalisis/realizaAnalisisMateria", [
    MateriasHomologarController::class,
    "updateMateriaHomologar",
]);

//** Actualizar el registro de la materia a homologar (informe completado) */
Route::post("doAnalisis/uploadPdfAnalisisFirmado", [
    MateriasHomologarController::class,
    "subirPdfAnalisisFirmado",
]);
