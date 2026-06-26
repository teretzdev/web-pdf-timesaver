<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

require_once __DIR__ . '/data.php';
require_once __DIR__ . '/logger.php';

/**
 * Client vault: uploads metadata goes through DataStore → MySQL (or JSON) via save().
 */
final class FileManager {
	private DataStore $store;
	private string $uploadDir;
	private Logger $logger;

	public function __construct(DataStore $store, string $uploadDir, ?Logger $logger = null) {
		$this->store = $store;
		$this->uploadDir = $uploadDir;
		$this->logger = $logger ?? new Logger();
	}

	public function uploadClientFile(string $clientId, ?string $projectId, array $uploadedFile): ?array {
		if (!isset($uploadedFile['tmp_name']) || !isset($uploadedFile['name'])) {
			$this->logger->error('Invalid file upload data');
			return null;
		}

		$tmpPath = $uploadedFile['tmp_name'];
		$originalName = $uploadedFile['name'];
		$size = $uploadedFile['size'] ?? 0;
		$mimeType = $uploadedFile['type'] ?? 'application/octet-stream';

		$extension = pathinfo($originalName, PATHINFO_EXTENSION);
		$filename = 'cf_' . bin2hex(random_bytes(8)) . '.' . $extension;
		$targetPath = $this->uploadDir . '/' . $filename;

		if (!is_dir($this->uploadDir)) {
			mkdir($this->uploadDir, 0755, true);
		}

		if (!move_uploaded_file($tmpPath, $targetPath)) {
			$this->logger->error('Failed to move uploaded file', ['tmp' => $tmpPath, 'target' => $targetPath]);
			return null;
		}

		$fileRecord = [
			'id' => 'cf_' . bin2hex(random_bytes(6)),
			'clientId' => $clientId,
			'projectId' => $projectId ?? '',
			'filename' => $filename,
			'originalName' => $originalName,
			'mimeType' => $mimeType,
			'size' => $size,
			'uploadedAt' => date(DATE_ATOM),
		];

		$this->store->addClientFileRecord($fileRecord);

		$this->logger->info('File uploaded successfully', ['fileId' => $fileRecord['id'], 'filename' => $originalName]);

		return $fileRecord;
	}

	public function getClientFiles(string $clientId, ?string $projectId = null): array {
		$files = $this->store->getClientFiles();

		$filtered = array_filter($files, function ($f) use ($clientId, $projectId) {
			if (($f['clientId'] ?? '') !== $clientId) {
				return false;
			}
			if ($projectId && ($f['projectId'] ?? '') !== $projectId) {
				return false;
			}
			return true;
		});

		return array_values($filtered);
	}

	public function deleteClientFile(string $fileId): bool {
		$removed = $this->store->removeClientFileById($fileId);
		if ($removed === null) {
			return false;
		}

		$filename = $removed['filename'] ?? null;
		if ($filename) {
			$filePath = $this->uploadDir . '/' . $filename;
			if (file_exists($filePath)) {
				unlink($filePath);
			}
		}

		$this->logger->info('File deleted', ['fileId' => $fileId]);
		return true;
	}

	public function getFileUrl(string $fileId): ?string {
		foreach ($this->store->getClientFiles() as $file) {
			if (($file['id'] ?? '') === $fileId) {
				return '/uploads/' . urlencode((string)($file['filename'] ?? ''));
			}
		}

		return null;
	}
}
