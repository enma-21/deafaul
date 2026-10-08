<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Envoltorio delgado sobre la API de WhatsApp Cloud (Meta) para mandar mensajes salientes.
 */
final class M2Wab_Client {
    private const API_VERSION = 'v21.0';

    public static function enviar_texto(string $to, string $body): string {
        $phone_number_id = M2Wab_Settings::phone_number_id();
        $access_token = M2Wab_Settings::access_token();

        if ($phone_number_id === '' || $access_token === '') {
            self::log('No se puede enviar: falta phone_number_id o access_token en los ajustes.');
            return '';
        }

        $url = sprintf('https://graph.facebook.com/%s/%s/messages', self::API_VERSION, rawurlencode($phone_number_id));

        $response = wp_remote_post($url, [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => $body],
            ]),
        ]);

        if (is_wp_error($response)) {
            self::log('Error de red enviando mensaje: ' . $response->get_error_message());
            return '';
        }

        $status = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($status < 200 || $status >= 300 || !is_array($data)) {
            self::log('WhatsApp API rechazó el envío (HTTP ' . $status . '): ' . wp_remote_retrieve_body($response));
            return '';
        }

        return (string) ($data['messages'][0]['id'] ?? '');
    }

    private static function log(string $mensaje): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[m2base-whatsapp-bot] ' . $mensaje);
        }
    }
}
