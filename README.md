Projeto utilizando gRPC
# Mega-Sena gRPC Architecture (Laravel + Go)

Este repositório contém uma solução distribuída de alta performance para geração e simulação de apostas da Mega-Sena, demonstrando a comunicação poliglota em tempo real via **gRPC (HTTP/2 + Protocol Buffers)** entre um cliente **Laravel (PHP)** e um microserviço em **Go**.

---

## 🏗️ Arquitetura do Sistema

+------------------------------------+             +------------------------------------+
|       megasena-grpc (PHP)          |             |       megasena-server (Go)         |
|  Cliente gRPC / Laravel Artisan    |             |  Servidor gRPC Multithreaded       |
|                                    |   gRPC      |                                    |
|  - Executa comandos CLI            | ----------> |  - Processamento paralelo          |
|  - Mede tempo e exibe métricas     |  (HTTP/2)   |  - Uso de todas as CPUs            |
|  - Trata o payload e formatação    | <---------- |  - ~18 milhões de jogos/seg         |
+------------------------------------+             +------------------------------------+

### Componentes

1. **`megasena-server` (Servidor em Go):**
   * Responsável pelas regras de negócio pesadas e sorteios estatísticos.
   * Utiliza Goroutines e primitivas atômicas (`sync/atomic`) para paralelizar os sorteios em todas as CPUs disponíveis no sistema.
   * Escuta requisições gRPC na porta `:50051`.

2. **`megasena-grpc` (Cliente em Laravel):**
   * Interface CLI desenvolvida com Artisan Commands.
   * Serializa os parâmetros via Protocol Buffers e consome o servidor Go usando a extensão nativa C do gRPC para PHP.
   * Apresenta estatísticas detalhadas ao usuário (tempo decorrido, taxa de processamento por segundo e custo financeiro estimado).

---

## 🛠️ Tecnologias Utilizadas

* **Linguagens:** Go (1.20+) | PHP (8.1+)
* **Framework Web:** Laravel
* **Comunicação:** gRPC | Protocol Buffers v3 | HTTP/2
* **Concorrência:** Go Routines + Channels + Atomic Operations

---

## 📄 Contrato gRPC (`proto/megasena.proto`)

O contrato unificado de comunicação define as mensagens e os métodos expostos pelo serviço Go:

```protobuf
syntax = "proto3";

package megasena;

option php_namespace = "App\\Grpc\\Megasena";
option php_metadata_namespace = "App\\Grpc\\GPBMetadata";
option go_package = "megasena-server/proto";

service MegasenaService {
  rpc GerarAposta (ApostaRequest) returns (ApostaResponse);
  rpc SimularSorteio (SimulacaoRequest) returns (SimulacaoResponse);
}

message ApostaRequest {
  int32 quantidade_dezenas = 1;
}

message ApostaResponse {
  repeated int32 numeros = 1;
  string data_geracao = 2;
}

message SimulacaoRequest {
  repeated int32 dezenas_alvo = 1;
}

message SimulacaoResponse {
  int64 total_tentativas = 1;
  double tempo_segundos = 2;
  repeated int32 numeros = 3;
}
🚀 Como Executar os Projetos
Pré-requisitos
Go (1.20 ou superior)

PHP (8.1 ou superior) com as extensões ext-grpc e ext-protobuf habilitadas

Compilador protoc instalado na máquina

1. Servidor Go (megasena-server)
Navegue até a pasta do servidor Go, compile o contrato .proto e inicie o serviço:

Bash
cd ~/megasena-server

# Compila os stubs Go a partir do contrato proto
protoc --proto_path=proto \
       --go_out=proto --go_opt=paths=source_relative \
       --go-grpc_out=proto --go-grpc_opt=paths=source_relative \
       proto/megasena.proto

# Baixa as dependências e inicia o servidor gRPC
go mod tidy
go run main.go
Saída esperada: 🚀 Servidor gRPC de Alta Performance rodando na porta :50051 (N CPUs disponíveis)...

2. Cliente Laravel (megasena-grpc)
Em outro terminal, acesse a pasta do cliente Laravel, compile o contrato Proto para PHP e execute os comandos de teste:

Bash
cd ~/megasena-grpc

# Compila o contrato proto para classes PHP
protoc --proto_path=proto \
       --php_out=. \
       --grpc_out=. \
       --plugin=protoc-gen-grpc=$(which grpc_php_plugin) \
       proto/megasena.proto

# Ajusta os namespaces e carrega o Autoload
cp -r App/* app/ && rm -rf App
composer dump-autoload
📊 Comandos e Exemplos de Uso
A. Gerar Aposta Simples
Gera um jogo aleatório de 6 a 15 dezenas no servidor Go:

Bash
php artisan grpc:teste --dezenas=6
Exemplo de Saída:

Plaintext
Iniciando requisição gRPC para gerar 6 dezenas...
Resposta recebida com sucesso!
Números da aposta: 8, 25, 27, 29, 53, 56
Data de geração: 11/08/2026 19:27:35
B. Simulação Bruta de Concurso (Sena)
Dispara um processo paralelo no Go que sorteia continuamente conjuntos de 6 dezenas até encontrar a combinação exata enviada pelo usuário:

Bash
php artisan grpc:simular 02 05 10 35 40 53
Exemplo de Saída:

Plaintext
🎯 Alvo do Sorteio: 2, 5, 10, 35, 40, 53
⚡ Disparando simulação concorrente em Go via gRPC...

=== RESULTADO DA SIMULAÇÃO ===
Dezenas encontradas: 2, 5, 10, 35, 40, 53
Jogadas necessárias: 72.325.606
Tempo decorrido: 4,00 segundos
Processamento do Go: 18.067.898 sorteios/segundo
Custo estimado em apostas reais: R$ 361.628.030,00
⚡ Performance
Durante os testes de benchmark, a implementação multithreaded em Go atingiu taxas superiores a 18 milhões de sorteios por segundo, demonstrando a baixíssima latência na serialização binária do gRPC e na execução paralela em memória.


