<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Controller;

use Evgenijfaustov\DebugSnapshotBundle\Service\SnapshotExporter;
use Evgenijfaustov\DebugSnapshotBundle\Service\SnapshotImporter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class SnapshotController
{
    private SnapshotExporter $exporter;
    private SnapshotImporter $importer;
    private AuthorizationCheckerInterface $authorizationChecker;
    private bool $httpEnabled;
    private string $httpRole;

    public function __construct(
        SnapshotExporter $exporter,
        SnapshotImporter $importer,
        AuthorizationCheckerInterface $authorizationChecker,
        bool $httpEnabled,
        string $httpRole
    ) {
        $this->exporter = $exporter;
        $this->importer = $importer;
        $this->authorizationChecker = $authorizationChecker;
        $this->httpEnabled = $httpEnabled;
        $this->httpRole = $httpRole;
    }

    #[Route('/_debug/snapshot/export/{profile}/{id}', name: 'debug_snapshot_export', methods: ['POST'])]
    public function export(string $profile, string $id, Request $request): BinaryFileResponse
    {
        $this->assertAccessAllowed();

        $anonymize = filter_var($request->query->get('anonymize', '0'), FILTER_VALIDATE_BOOL);
        $archivePath = $this->exporter->export($profile, $id, sys_get_temp_dir(), $anonymize);

        $response = new BinaryFileResponse($archivePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($archivePath));
        $response->deleteFileAfterSend(true);

        return $response;
    }

    #[Route('/_debug/snapshot/import', name: 'debug_snapshot_import', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        $this->assertAccessAllowed();

        $anonymize = filter_var($request->query->get('anonymize', '0'), FILTER_VALIDATE_BOOL);
        $file = $request->files->get('snapshot');
        if (!$file instanceof UploadedFile) {
            throw new BadRequestHttpException('Snapshot file is required.');
        }

        $this->importer->import($file->getPathname(), $anonymize);

        return new JsonResponse(['status' => 'ok']);
    }

    private function assertAccessAllowed(): void
    {
        if (!$this->httpEnabled) {
            throw new NotFoundHttpException();
        }

        if (!$this->authorizationChecker->isGranted($this->httpRole)) {
            throw new AccessDeniedHttpException();
        }
    }
}
