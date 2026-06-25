<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Levée lorsqu'aucun reader ne sait traiter l'extension d'un fichier.
 */
final class UnsupportedFileFormatException extends \RuntimeException
{
}
