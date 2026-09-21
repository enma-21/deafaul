<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Genera la respuesta a un mensaje entrante. Es una implementación provisional
 * (búsqueda directa por palabras clave sobre el catálogo ya sincronizado) mientras
 * no se defina el proveedor de IA que va a reemplazar esta clase. El resto del
 * bot (webhook, seguridad, envío, persistencia) no depende de este método.
 *
 * @see M2Base_Catalogo_ML_Repository::buscar()
 */
final class M2Wab_Responder {
    const MAX_RESULTADOS = 5;

    /**
     * @param array $contexto Estado de la conversación (última marca/modelo/texto buscado).
     * @return array{0:string,1:array} [respuesta, contexto actualizado]
     */
    public static function responder(string $texto, array $contexto): array {
        $texto = trim($texto);

        if ($texto === '') {
            return [self::mensaje_ayuda(), $contexto];
        }

        if (!class_exists('M2Base_Catalogo_ML_Repository') || !class_exists('M2Base_Catalogo_ML_Parser')) {
            return ['Por ahora no puedo consultar el catálogo. Intenta de nuevo en unos minutos.', $contexto];
        }

        $vehiculo = M2Base_Catalogo_ML_Parser::parse_titulo($texto);

        $intentos = [];
        $intentos[] = [
            'texto' => $texto,
            'vehicle_brand' => $vehiculo['vehicle_brand'],
            'vehicle_model' => $vehiculo['vehicle_model'],
        ];
        if ($vehiculo['vehicle_brand'] !== '' || $vehiculo['vehicle_model'] !== '') {
            $intentos[] = [
                'vehicle_brand' => $vehiculo['vehicle_brand'],
                'vehicle_model' => $vehiculo['vehicle_model'],
            ];
        }
        $intentos[] = ['texto' => $texto];

        $resultados = [];
        $total = 0;
        foreach ($intentos as $filtros) {
            $filtros['por_pagina'] = self::MAX_RESULTADOS;
            $filtros['pagina'] = 1;
            $resultados = M2Base_Catalogo_ML_Repository::buscar($filtros);
            if (!empty($resultados)) {
                $total = M2Base_Catalogo_ML_Repository::contar($filtros);
                break;
            }
        }

        $contexto_nuevo = [
            'ultimo_texto' => $texto,
            'vehicle_brand' => $vehiculo['vehicle_brand'],
            'vehicle_model' => $vehiculo['vehicle_model'],
        ];

        if (empty($resultados)) {
            return [
                "No encontré repuestos para \"{$texto}\". Prueba escribiendo la marca y modelo del vehículo, o el número de parte (ej: \"pastillas de freno toyota corolla 2015\").",
                $contexto_nuevo,
            ];
        }

        return [self::formatear_resultados($resultados, $total), $contexto_nuevo];
    }

    private static function mensaje_ayuda(): string {
        return "¡Hola! Cuéntame qué repuesto buscas: la marca y modelo del vehículo (ej: \"pastillas de freno Toyota Corolla 2015\") o el número de parte, y te muestro lo que tenemos disponible.";
    }

    private static function formatear_resultados(array $resultados, int $total): string {
        $lineas = [];
        foreach ($resultados as $fila) {
            $precio = number_format((float) $fila['price'], 2) . ' ' . $fila['currency_id'];
            $lineas[] = "• {$fila['title']} — {$precio}\n  {$fila['permalink']}";
        }

        $encabezado = $total > count($resultados)
            ? "Encontré {$total} resultados, aquí van los primeros " . count($resultados) . ":\n\n"
            : "Encontré esto:\n\n";

        return $encabezado . implode("\n\n", $lineas);
    }
}
