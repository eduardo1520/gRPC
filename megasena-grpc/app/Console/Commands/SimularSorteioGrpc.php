<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Grpc\Megasena\MegasenaServiceClient;
use App\Grpc\Megasena\SimulacaoRequest;
use Grpc\ChannelCredentials;

class SimularSorteioGrpc extends Command
{
    /**
     * O nome e a assinatura do comando no terminal.
     */
    protected $signature = 'grpc:simular {dezenas* : 6 números separados por espaço (ex: 8 25 27 29 53 56)}';

    /**
     * A descrição do comando.
     */
    protected $description = 'Simula sorteios via gRPC em Go até acertar os 6 números informados';

    public function handle()
    {
        $dezenas = array_map('intval', $this->argument('dezenas'));

        if (count($dezenas) !== 6) {
            $this->error("Erro: Informe exatamente 6 dezenas separadas por espaço.");
            return 1;
        }

        sort($dezenas);
        $this->info("🎯 Alvo do Sorteio: " . implode(', ', $dezenas));
        $this->line("⚡ Disparando simulação concorrente em Go via gRPC...");

        // Instancia o cliente gRPC apontando para o servidor Go
        $client = new MegasenaServiceClient('localhost:50051', [
            'credentials' => ChannelCredentials::createInsecure(),
        ]);

        // Prepara o payload da requisição com o array de dezenas
        $request = new SimulacaoRequest();
        $request->setDezenasAlvo($dezenas);

        // Executa a chamada remota síncrona
        [$response, $status] = $client->SimularSorteio($request)->wait();

        if ($status->code !== \Grpc\STATUS_OK) {
            $this->error("Erro gRPC [Código {$status->code}]: " . $status->details);
            return 1;
        }

        $tentativas = $response->getTotalTentativas();
        $segundos = $response->getTempoSegundos();
        $jogosPorSegundo = $segundos > 0 ? number_format($tentativas / $segundos, 0, ',', '.') : 0;
        $custoApostas = number_format($tentativas * 5.00, 2, ',', '.'); // Preço base da aposta simples R$ 5,00

        $this->newLine();
        $this->info("=== RESULTADO DA SIMULAÇÃO ===");
        $this->line("Dezenas encontradas: " . implode(', ', iterator_to_array($response->getNumeros())));
        $this->line("Jogadas necessárias: " . number_format($tentativas, 0, ',', '.'));
        $this->line("Tempo decorrido: " . number_format($segundos, 2, ',', '.') . " segundos");
        $this->line("Processamento do Go: " . $jogosPorSegundo . " sorteios/segundo");
        $this->line("Custo estimado em apostas reais: R$ " . $custoApostas);

        return 0;
    }
}
