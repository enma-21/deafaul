<?php

if (!defined('ABSPATH')) {
    exit;
}

final class M2Wab_Settings {
    private const OPTION = 'm2wab_settings';

    public static function all(): array {
        return wp_parse_args(get_option(self::OPTION, []), [
            'verify_token' => '',
            'access_token' => '',
            'phone_number_id' => '',
            'waba_id' => '',
            'app_secret' => '',
        ]);
    }

    public static function verify_token(): string {
        return (string) self::all()['verify_token'];
    }

    public static function phone_number_id(): string {
        return (string) self::all()['phone_number_id'];
    }

    public static function waba_id(): string {
        return (string) self::all()['waba_id'];
    }

    public static function access_token(): string {
        return self::decrypt_secret((string) self::all()['access_token']);
    }

    public static function app_secret(): string {
        return self::decrypt_secret((string) self::all()['app_secret']);
    }

    public static function guardar(array $datos): void {
        $settings = self::all();

        $settings['verify_token'] = sanitize_text_field((string) ($datos['verify_token'] ?? $settings['verify_token']));
        $settings['phone_number_id'] = sanitize_text_field((string) ($datos['phone_number_id'] ?? $settings['phone_number_id']));
        $settings['waba_id'] = sanitize_text_field((string) ($datos['waba_id'] ?? $settings['waba_id']));

        if (!empty($datos['access_token'])) {
            $settings['access_token'] = self::encrypt_secret((string) $datos['access_token']);
        }
        if (!empty($datos['app_secret'])) {
            $settings['app_secret'] = self::encrypt_secret((string) $datos['app_secret']);
        }

        update_option(self::OPTION, $settings, false);
    }

    private static function crypto_key(): string {
        return hash('sha256', wp_salt('auth') . '|m2base-whatsapp-bot', true);
    }

    private static function encrypt_secret(string $value): string {
        if ($value === '' || !function_exists('openssl_encrypt')) {
            return $value;
        }

        $iv = random_bytes(16);
        $cipher = openssl_encrypt(
            $value,
            'aes-256-cbc',
            self::crypto_key(),
            OPENSSL_RAW_DATA,
            $iv
        );

        return 'enc:' . base64_encode($iv . $cipher);
    }

    private static function decrypt_secret(string $value): string {
        if ($value === '' || strpos($value, 'enc:') !== 0 || !function_exists('openssl_decrypt')) {
            return $value;
        }

        $decoded = base64_decode(substr($value, 4), true);
        if ($decoded === false || strlen($decoded) <= 16) {
            return '';
        }

        $iv = substr($decoded, 0, 16);
        $cipher = substr($decoded, 16);
        $plain = openssl_decrypt(
            $cipher,
            'aes-256-cbc',
            self::crypto_key(),
            OPENSSL_RAW_DATA,
            $iv
        );

        return is_string($plain) ? $plain : '';
    }
}
