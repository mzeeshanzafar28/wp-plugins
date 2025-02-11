<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
#[ \AllowDynamicProperties ]

class Contiant_API {
    private $api_url;
    private $client_id;
    private $client_secret;
    private $token;

    public function __construct() {
        $contiant_settings = get_option( 'woocommerce_contiant_settings' );

        $this->api_url = $contiant_settings[ 'api_url' ] ?? 'https://api.contiant.com';
        $this->client_id = $contiant_settings[ 'client_id' ] ?? '';
        $this->client_secret = $contiant_settings[ 'client_secret' ] ?? '';
    }

    public function authenticate() {
        $url = $this->api_url . '/token';
        $args = array(
            'body' => array(
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
            ),
        );

        $response = wp_remote_post( $url, $args );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $this->token = $body[ 'access_token' ];

        return $this->token;
    }

    public function create_payment( $payment_data ) {
        $url = $this->api_url . '/payments';
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode( $payment_data ),
        );

        $response = wp_remote_post( $url, $args );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        return json_decode( wp_remote_retrieve_body( $response ), true );
    }
}