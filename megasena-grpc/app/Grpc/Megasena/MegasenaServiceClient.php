<?php
// GENERATED CODE -- DO NOT EDIT!

namespace App\Grpc\Megasena;

/**
 */
class MegasenaServiceClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \App\Grpc\Megasena\ApostaRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GerarAposta(\App\Grpc\Megasena\ApostaRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/megasena.MegasenaService/GerarAposta',
        $argument,
        ['\App\Grpc\Megasena\ApostaResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \App\Grpc\Megasena\SimulacaoRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SimularSorteio(\App\Grpc\Megasena\SimulacaoRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/megasena.MegasenaService/SimularSorteio',
        $argument,
        ['\App\Grpc\Megasena\SimulacaoResponse', 'decode'],
        $metadata, $options);
    }

}
