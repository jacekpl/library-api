<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DocumentationController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    #[Route('/docs', name: 'api_documentation', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new Response(
            (string) file_get_contents($this->projectDir.'/public/docs/index.html'),
            Response::HTTP_OK,
            ['Content-Type' => 'text/html'],
        );
    }
}
