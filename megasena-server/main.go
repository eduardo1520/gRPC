package main

import (
	"context"
	"fmt"
	"math/rand/v2"
	"net"
	"runtime"
	"sort"
	"sync"
	"sync/atomic"
	"time"

	pb "megasena-server/proto"

	"google.golang.org/grpc"
	"google.golang.org/grpc/codes"
	"google.golang.org/grpc/status"
)

type server struct {
	pb.UnimplementedMegasenaServiceServer
}

func (s *server) GerarAposta(ctx context.Context, req *pb.ApostaRequest) (*pb.ApostaResponse, error) {
	qtd := req.GetQuantidadeDezenas()
	if qtd < 6 || qtd > 15 {
		return nil, status.Errorf(codes.InvalidArgument, "A quantidade de dezenas deve ser entre 6 e 15")
	}

	dezenasMap := make(map[int32]bool)
	for len(dezenasMap) < int(qtd) {
		dezenasMap[int32(rand.IntN(60)+1)] = true
	}

	var numeros []int32
	for num := range dezenasMap {
		numeros = append(numeros, num)
	}
	sort.Slice(numeros, func(i, j int) bool { return numeros[i] < numeros[j] })

	return &pb.ApostaResponse{
		Numeros:     numeros,
		DataGeracao: time.Now().Format("02/01/2006 15:04:05"),
	}, nil
}

func (s *server) SimularSorteio(ctx context.Context, req *pb.SimulacaoRequest) (*pb.SimulacaoResponse, error) {
	alvo := req.GetDezenasAlvo()
	if len(alvo) != 6 {
		return nil, status.Errorf(codes.InvalidArgument, "Informe exatamente 6 dezenas para o sorteio alvo.")
	}

	// Ordena o alvo para comparação rápida de slices
	alvoSlice := make([]int32, 6)
	copy(alvoSlice, alvo)
	sort.Slice(alvoSlice, func(i, j int) bool { return alvoSlice[i] < alvoSlice[j] })

	inicio := time.Now()
	var totalTentativas atomic.Int64
	var encontrou atomic.Bool

	numWorkers := runtime.NumCPU()
	var wg sync.WaitGroup

	for i := 0; i < numWorkers; i++ {
		wg.Add(1)
		go func() {
			defer wg.Done()
			var localCount int64

			for !encontrou.Load() {
				// Sorteia 6 dezenas ordenadas
				var aposta [6]int32
				count := 0
				for count < 6 {
					num := int32(rand.IntN(60) + 1)
					duplicado := false
					for j := 0; j < count; j++ {
						if aposta[j] == num {
							duplicado = true
							break
						}
					}
					if !duplicado {
						aposta[count] = num
						count++
					}
				}

				sort.Slice(aposta[:], func(i, j int) bool { return aposta[i] < aposta[j] })

				localCount++

				// Verifica se acertou todas as 6 dezenas
				if aposta[0] == alvoSlice[0] &&
					aposta[1] == alvoSlice[1] &&
					aposta[2] == alvoSlice[2] &&
					aposta[3] == alvoSlice[3] &&
					aposta[4] == alvoSlice[4] &&
					aposta[5] == alvoSlice[5] {
					
					if encontrou.CompareAndSwap(false, true) {
						totalTentativas.Add(localCount)
					}
					return
				}

				// Atualiza o contador global periodicamente para reduzir contenção
				if localCount%100000 == 0 {
					totalTentativas.Add(100000)
					localCount = 0
				}
			}
			if localCount > 0 {
				totalTentativas.Add(localCount)
			}
		}()
	}

	wg.Wait()
	duracao := time.Since(inicio).Seconds()

	return &pb.SimulacaoResponse{
		TotalTentativas: totalTentativas.Load(),
		TempoSegundos:   duracao,
		Numeros:         alvoSlice,
	}, nil
}

func main() {
	lis, err := net.Listen("tcp", ":50051")
	if err != nil {
		panic(err)
	}

	grpcServer := grpc.NewServer()
	pb.RegisterMegasenaServiceServer(grpcServer, &server{})

	fmt.Printf("🚀 Servidor gRPC de Alta Performance rodando na porta :50051 (%d CPUs disponíveis)...\n", runtime.NumCPU())
	if err := grpcServer.Serve(lis); err != nil {
		panic(err)
	}
}
