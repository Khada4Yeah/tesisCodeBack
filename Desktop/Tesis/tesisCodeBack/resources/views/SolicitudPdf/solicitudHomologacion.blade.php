<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <style>
        body {
            margin: 0;
            line-height: 25px;
            padding: 1.5cm 1.5cm;
            font: 10pt;
            text-align: justify;
        }

        .table_r {
            overflow-x: auto
        }

        table,
        th,
        td {
            border: 1px solid black;
            border-collapse: collapse;
        }

        table {
            width: 100%
        }
    </style>
</head>

<body>
    Portoviejo, {{ $fecha_actual }} <br><br>
    Señor Vicedecano/a de la Carrera de {{ $datos_carrera[0]->escuelad_e }} <br>
    UNIVERSIDAD TÉCNICA DE MANABÍ <br>
    Presente
    <br><br><br>
    <p>
        Yo <b>{{ $datos_carrera[0]->apellidos_e . " " . $datos_carrera[0]->nombres_e }}</b>, con # de CI
        <b>{{$datos_carrera[0]->cedula_e }}</b> estudiante del <b>{{ $nivel }}</b> semestre y/o nivel de la
        Carrera de <b>{{$datos_carrera[0]->escuelao_e }}</b> de la Facultad <b>{{ $datos_carrera[0]->facultad_e }}</b>
        de la
        <b>{{ $datos_carrera[0]->universidad_e }}</b> por medio de la presente solicito cambio a la Carrera de
        <b>{{$datos_carrera[0]->escuelad_e }}</b> Modalidad <b>{{$datos_carrera[0]->modalidad_e }}</b>, misma que usted
        dirige para el Período Académico {{ $perido_academico->nombre }}.
        <br>
    </p>

    <p>Las asignaturas que requieren análisis de homologación son las siguientes:</p>

    <div class="table_r">
        <table>
            <thead>
                <tr>
                    <th>Asignatura</th>
                    <th>Nivel</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($materias_solicitud as $soli)
                <tr>
                    <td>{{ $soli->materia }}</td>
                    <td>{{ $soli->nivel }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p>Para tal efecto, adjunto la documentación requerida en el formato establecido (PDF).</p>

    <p>Declaro que toda la documentación proporcionada es verídica y auténtica, por lo que autorizo mediante la presente
        si lo determinan necesario verificar la información a quienes la certifican.</p>

    <p>Asimismo, declaro no haber realizado con anterioridad el cambio a otra carrera o Institución de Educacion
        Superior.
    </p>

    <p>Sin otro particular le anticipo mi agradecimiento.</p>

    <br><br><br>

    <p>Atentamente, <br>{{ $datos_carrera[0]->apellidos_e . " " . $datos_carrera[0]->nombres_e }}</p>



</body>

</html>