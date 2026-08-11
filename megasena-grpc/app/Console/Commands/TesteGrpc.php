<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Grpc\Megasena\MegasenaServiceClient;
use App\Grpc\Megasena\ApostaRequest;
use Grpc\ChannelCredentials;

class TesteGrpc extends Command
{
    /**
     * O nome e a assinatura do comando no terminal.
     */
    protected $signature = 'grpc:teste {--dezenas=6}';

    /**
     * A descrição do comando.
     */
    protected $description = 'Testa a chamada ao serviço gRPC da Mega-Sena';

    public function handle()
    {
        $dezenas = (int) $this->option('dezenas');

        $this->info("Iniciando requisição gRPC para gerar {$dezenas} dezenas...");

        // Instancia o cliente gRPC apontando para o servidor (localhost:50051)
        $client = new MegasenaServiceClient('localhost:50051', [
            'credentials' => ChannelCredentials::createInsecure(),
        ]);

        // Monta a mensagem de Request gerada pelo Protobuf
        $request = new ApostaRequest();
        $request->setQuantidadeDezenas($dezenas);

        // Executa a chamada RPC remota e aguarda a resposta
        [$response, $status] = $client->GerarAposta($request)->wait();

        // Tratamento de erros de conexão/servidor gRPC
        if ($status->code !== \Grpc\STATUS_OK) {
            $this->error("Erro no gRPC [Código {$status->code}]: " . $status->details);
            $this->line("Dica: Certifique-se de que o servidor gRPC está rodando na porta 50051.");
            return 1;
        }

        // Exibe os dados retornados no console
        $numeros = iterator_to_array($response->getNumeros());
        
        $this->info(" Resposta recebida com sucesso!");
        $this->line("Números da aposta: " . implode(', ', $numeros));
        $this->line("Data de geração: " . $response->getDataGeracao());

        return 0;
    }
}