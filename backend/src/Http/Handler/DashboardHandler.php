<?php

declare(strict_types=1);

namespace Payments\Http\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Payments\Application\GetDashboardReport;
use Payments\Domain\InvalidLedger;
use Payments\Http\Request\DashboardQuery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

final readonly class DashboardHandler implements RequestHandlerInterface
{
    public function __construct(private GetDashboardReport $getReport, private LoggerInterface $logger)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $reference = bin2hex(random_bytes(8));
        try {
            $query = DashboardQuery::fromArray($request->getQueryParams());
        } catch (\InvalidArgumentException) {
            $this->logger->notice('invalid_query', ['reference' => $reference]);
            return $this->json(['message' => 'Choose a valid date and period.', 'reference' => $reference], 400);
        }
        try {
            return $this->json($this->getReport->execute($query));
        } catch (\Throwable $error) {
            $code = $error instanceof InvalidLedger ? $error->getMessage() : 'unexpected_error';
            $this->logger->error($code, ['reference' => $reference]);
            return $this->json([
                'message' => 'Payments are unavailable. Please try again.',
                'reference' => $reference,
            ], 503);
        }
    }

    private function json(mixed $data, int $status = 200): ResponseInterface
    {
        return new JsonResponse($data, $status, ['Cache-Control' => 'no-store']);
    }
}
