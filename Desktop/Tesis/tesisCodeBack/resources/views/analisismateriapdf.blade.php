<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <style>
        @page {
            size: landscape;
        }

        body {
            font-family: sans-serif;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid black;
            font-size: 11pt;
            padding: 3px;
            text-align: left;
        }
    </style>
</head>

<body>

    <table>

        <tbody>

            <tr>
                <th colspan="8" style="text-align: center; font-size: 18pt">UNIVERSIDAD TÉCNICA DE MANABÍ</th>
            </tr>

            <tr>
                <td colspan="8" style="text-align: center; font-size: 18pt">
                    @if (rtrim($facultad[0]->nombre) == "INSTITUTO DE CIENCIAS BÁSICAS")
                    <b>{{ rtrim($facultad[0]->nombre) }}</b>
                    @else
                    <b>{{ "FACULTAD DE " . rtrim($facultad[0]->nombre) }}</b>
                    @endif

                </td>
            </tr>

            <tr>
                <td colspan="8" style="text-align: center; background-color: #c3deb1; font-size: 14pt">
                    <b>
                        FORMULARIO PARA PRESENTAR EL INFORME DE RECONOCIMIENTO U
                        HOMOLOGACIÓN DE ESTUDIOS
                    </b>
                </td>
            </tr>

            <tr>
                <td>
                    <b>FECHA INFORME</b>
                </td>
                <td>
                    <b>DÍA:</b>
                </td>
                <td style="text-align: center;">
                    {{ $fecha_actual[0] }}
                </td>
                <td>
                    <b>MES:</b>
                </td>
                <td style="text-align: center;">
                    {{ $fecha_actual[1] }}
                </td>
                <td>
                    <b>AÑO:</b>
                </td>
                <td colspan="2" style="text-align: center;">
                    {{ $fecha_actual[2] }}
                </td>
            </tr>

            <tr>
                <td colspan="8" style="text-align: center; background-color: #c3deb1; font-size: 14pt">
                    <b>DATOS GENERALES DEL ESTUDIANTE</b>
                </td>
            </tr>

            <tr>
                <td><b>APELLIDOS Y NOMBRES (COMPLETOS):</b></td>
                <td colspan="3" style="font-size: 12pt">
                    {{ $detalleSolicitud[0]->apellidos . " " . $detalleSolicitud[0]->nombres }}
                </td>
                <td colspan="2"><b>N° CEDULA / PASAPORTE:</b></td>
                <td colspan="2" style="font-size: 12pt">{{ $detalleSolicitud[0]->cedula }}</td>
            </tr>

            <tr>
                <td><b>UNIVERSIDAD DE DONDE PROVIENE:</b></td>
                <td colspan="7" style="font-size: 12pt">
                    {{ $detalleSolicitud[0]->universidad }}
                </td>
            </tr>

            <tr>
                <td>
                    <b>FACULTAD/CARRERA DE LA CUAL PROVIENE:</b>
                </td>
                <td colspan="7" style="font-size: 12pt">{{ $detalleSolicitud[0]->escuela_origen }}</td>
            </tr>

            <tr>
                <td colspan="8" style="text-align: center; background-color: #c3deb1; font-size: 12pt">
                    <b>
                        ANÁLISIS COMPARATIVO DE
                        CONTENIDOS, CONSIDERANDO SU SIMILITUD
                        Y LAS HORAS PLANIFICADAS
                        EN CADA
                        ASIGNATURA; 80% DE CONTENIDO,
                        PROFUNDIDAD Y CARGA HORARIA (PERÍODO MENOR A 10 AÑOS) ART. 99 NUMERAL a DEL REGLAMENTO DE
                        RÉGIMEN
                        ACADÉMICO DEL CES
                    </b>
                </td>
            </tr>

            <tr>
                <td style="width: 20%">
                    <b>
                        NOMBRE DE LA ASIGNATURA DE LA INSTITUCIÓN DE PROCEDENCIA
                    </b>
                </td>

                <td style="width: 10%">
                    <b>N° CRÉDITOS U. PROVIENE</b>
                </td>

                <td style="width: 10%">
                    <b>
                        AÑO DE APROBACIÓN DE LA ASIGNATUTA
                    </b>
                </td>

                <td style="width: 20%">
                    <b>
                        NOMBRE DE LA ASIGNATURA EN LA UTM
                    </b>
                </td>

                <td>
                    <b>
                        N° DE CRÉDITOS UTM
                    </b>
                </td>

                <td>
                    <b>
                        PORCENTAJE DE SIMILITUD DE CONTENIDOS
                    </b>
                </td>

                <td style="width: 10%">
                    <b>
                        NOTA QUE DEBE ASENTARSE
                    </b>
                </td>

                <td>
                    <b>
                        CONCLUSIÓN - OBSERVACIÓN
                    </b>
                </td>
            </tr>

            @foreach ($materiasSolicitud as $mate)
            <tr>
                <td>{{ $mate->nombre_materia_procedencia }}</td>
                <td style="text-align: center;">{{ $mate->numero_creditos_procedencia }}</td>
                <td style="text-align: center;">{{ $mate->anio_aprobacion_materia }}</td>
                <td>{{ $mate->materia }}</td>
                <td style="text-align: center;">{{ $mate->creditos }}</td>
                <td style="text-align: center;">{{ $mate->porcentaje_similiutd_contenidos }}%</td>
                <td style="text-align: center;">{{ $mate->puntaje_asentar }}</td>
                <td>{{ $mate->observaciones }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <br>
    <br>

    <div style="text-align: center; font-weight: bold">
        _________________________________________________
        <br>
        Firma
        <br>
        {{ $docente[0]->nombre_docente }}
        <br>
        NOMBRE DEL DOCENTE QUE REALIZA EL ANÁLISIS COMPARATIVO
    </div>

</body>

</html>