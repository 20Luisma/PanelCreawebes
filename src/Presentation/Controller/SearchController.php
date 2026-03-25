<?php
namespace Presentation\Controller;

use Application\UseCase\SearchUseCase;

class SearchController {
    private SearchUseCase $searchUseCase;

    public function __construct(SearchUseCase $searchUseCase) {
        $this->searchUseCase = $searchUseCase;
    }

    public function handleRequest(array $getData, ?string $currentUser, bool $esAdmin, bool $overrideActive, ?int $overrideExpiry): void {
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');
        
        if (!$currentUser) {
            http_response_code(403);
            echo json_encode(["error" => "No autorizado"]);
            exit;
        }

        $query = $getData['q'] ?? '';
        $sistemaVisible = $esAdmin && isset($getData['sistema']) && $getData['sistema'] === '1';

        $resultados = $this->searchUseCase->performSearch($query, $currentUser, $esAdmin, $sistemaVisible, $overrideActive, $overrideExpiry);

        echo json_encode($resultados);
        exit;
    }
}
